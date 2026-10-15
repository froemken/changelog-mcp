<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Domain\Repository;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use StefanFroemken\ChangelogMcp\Service\Changelog;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ChangelogRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const TABLE = 'tx_changelogmcp_changelog';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function create(Changelog $changelog): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->insert(
            self::TABLE,
            [
                'title' => $changelog->getTitle(),
                'change_type' => ucfirst(strtolower($changelog->getChangeType())),
                'version_string' => $changelog->getVersionString(),
                'major_version' => $changelog->getMajorVersion(),
                'issue_number' => $changelog->getIssueNumber(),
                'tags' => $changelog->getTags(),
                'summary' => $changelog->getDescription(),
                'content' => $changelog->getMdContent(),
            ],
        );
    }

    public function truncate(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $connection->truncate(self::TABLE);
    }

    /**
     * Retrieves changelogs based on search term, version range, type, limit, and offset.
     *
     * @return array{totalCount: int, items: array<int, array<string, mixed>>}
     */
    public function getChangelogs(
        ?string $prompt,
        ?string $fromVersion = null,
        ?string $toVersion = null,
        ?string $changeType = null,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $queryBuilder = $this->getQueryBuilder();
        $words = $this->extractSearchWords($prompt);
        $constraints = $this->buildSearchConstraints($queryBuilder, $words);
        $constraints = array_merge($constraints, $this->buildVersionConstraints($queryBuilder, $fromVersion, $toVersion));

        $changeTypeConstraint = $this->buildChangeTypeConstraint($queryBuilder, $changeType);
        if ($changeTypeConstraint !== null) {
            $constraints[] = $changeTypeConstraint;
        }

        $queryBuilder->from(self::TABLE);
        if ($constraints !== []) {
            $queryBuilder->where($queryBuilder->expr()->and(...$constraints));
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalCount = (int)$countQueryBuilder->count('*')->executeQuery()->fetchOne();
        if ($totalCount === 0) {
            return ['totalCount' => 0, 'items' => []];
        }

        $scoreExpression = $this->buildScoreExpression($queryBuilder, $prompt, $words);
        $items = $queryBuilder
            ->select('*')
            ->addSelectLiteral($scoreExpression . ' AS score')
            ->orderBy('score', 'DESC')
            ->addOrderBy('major_version', 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return ['totalCount' => $totalCount, 'items' => $items];
    }

    public function getChangelogContentByUid(int $uid): ?string
    {
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('content')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();

        return $result['content'] ?? null;
    }

    private function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        return $queryBuilder;
    }

    /**
     * @return list<string>
     */
    private function extractSearchWords(?string $prompt): array
    {
        if ($prompt === null || $prompt === '') {
            return [];
        }

        $rawWords = array_values(array_filter(explode(' ', $prompt)));
        $stopWords = [
            'how', 'to', 'the', 'a', 'an', 'of', 'in', 'and', 'is', 'for', 'with', 'on', 'as', 'by', 'at', 'it', 'from',
            'what', 'why', 'where', 'when', 'who', 'which', 'do', 'does', 'did', 'have', 'has', 'had', 'are', 'was', 'were',
            'correctly', 'write', 'about', 'use', 'using', 'get', 'set', 'make', 'create',
        ];
        $nonSelective = ['typo3', 'cms'];

        $cleanedWords = [];
        foreach ($rawWords as $word) {
            if (!in_array(mb_strtolower($word), $stopWords, true)) {
                $cleanedWords[] = $word;
            }
        }

        $finalWords = [];
        foreach ($cleanedWords as $word) {
            if (!in_array(mb_strtolower($word), $nonSelective, true)) {
                $finalWords[] = $word;
            }
        }

        return $finalWords !== [] ? $finalWords : ($cleanedWords !== [] ? $cleanedWords : $rawWords);
    }

    /**
     * @param list<string> $words
     * @return list<CompositeExpression>
     */
    private function buildSearchConstraints(QueryBuilder $queryBuilder, array $words): array
    {
        if ($words === []) {
            return [];
        }

        $searchConditions = [];
        foreach ($words as $word) {
            $wordParam = $queryBuilder->createNamedParameter('%' . $word . '%');
            $searchConditions[] = $queryBuilder->expr()->like('title', $wordParam);
            $searchConditions[] = $queryBuilder->expr()->like('content', $wordParam);
        }

        return [$queryBuilder->expr()->or(...$searchConditions)];
    }

    /**
     * @return list<string>
     */
    private function buildVersionConstraints(
        QueryBuilder $queryBuilder,
        ?string $fromVersion,
        ?string $toVersion,
    ): array {
        $hasFrom = $fromVersion !== null && $fromVersion !== '';
        $hasTo = $toVersion !== null && $toVersion !== '';

        if ($hasFrom && $hasTo) {
            $fromMajor = (int)explode('.', $fromVersion)[0];
            $toMajor = (int)explode('.', $toVersion)[0];
            return [
                $queryBuilder->expr()->gte('major_version', $queryBuilder->createNamedParameter($fromMajor, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte('major_version', $queryBuilder->createNamedParameter($toMajor, Connection::PARAM_INT)),
            ];
        }
        if ($hasTo) {
            $toMajor = (int)explode('.', $toVersion)[0];
            return [
                $queryBuilder->expr()->lte('major_version', $queryBuilder->createNamedParameter($toMajor, Connection::PARAM_INT)),
            ];
        }
        if ($hasFrom) {
            $fromMajor = (int)explode('.', $fromVersion)[0];
            return [
                $queryBuilder->expr()->gte('major_version', $queryBuilder->createNamedParameter($fromMajor, Connection::PARAM_INT)),
            ];
        }
        return [];
    }

    private function buildChangeTypeConstraint(QueryBuilder $queryBuilder, ?string $changeType): ?string
    {
        if ($changeType === null || $changeType === '') {
            return null;
        }

        $normalizedType = ucfirst(strtolower($changeType));
        return $queryBuilder->expr()->eq(
            'change_type',
            $queryBuilder->createNamedParameter($normalizedType),
        );
    }

    /**
     * @param list<string> $words
     */
    private function buildScoreExpression(QueryBuilder $queryBuilder, ?string $prompt, array $words): string
    {
        $scoreParts = [];

        foreach ($words as $word) {
            $wordParam = $queryBuilder->createNamedParameter('%' . $word . '%');
            $scoreParts[] = 'CASE WHEN title LIKE ' . $wordParam . ' THEN 20 ELSE 0 END';
            $scoreParts[] = 'CASE WHEN content LIKE ' . $wordParam . ' THEN 5 ELSE 0 END';
        }

        if ($prompt !== null && $prompt !== '') {
            $scoreParts = array_merge($scoreParts, $this->buildIntentScoreParts($prompt));
        }

        return $scoreParts !== [] ? '(' . implode(' + ', $scoreParts) . ')' : '0';
    }

    /**
     * @return list<string>
     */
    private function buildIntentScoreParts(string $prompt): array
    {
        $promptLower = mb_strtolower($prompt);
        $scoreParts = [];

        $featureKeywords = ['implement', 'new', 'add', 'create', 'introduce', 'feature', 'introduction'];
        $deprecationKeywords = ['upgrade', 'deprecated', 'deprecation', 'migrate', 'migration', 'replace', 'replacement', 'obsolete'];
        $breakingKeywords = ['removed', 'removal', 'delete', 'deleted', 'breaking', 'broken', 'remove'];

        if ($this->matchesAnyKeyword($promptLower, $featureKeywords)) {
            $scoreParts[] = "CASE WHEN change_type = 'Feature' THEN 50 ELSE 0 END";
        }
        if ($this->matchesAnyKeyword($promptLower, $deprecationKeywords)) {
            $scoreParts[] = "CASE WHEN change_type = 'Deprecation' THEN 50 ELSE 0 END";
        }
        if ($this->matchesAnyKeyword($promptLower, $breakingKeywords)) {
            $scoreParts[] = "CASE WHEN change_type = 'Breaking' THEN 50 ELSE 0 END";
        }

        return $scoreParts;
    }

    /**
     * @param list<string> $keywords
     */
    private function matchesAnyKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
