<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Domain\Repository;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use StefanFroemken\ChangelogMcp\Service\Changelog;
use TYPO3\CMS\Core\Core\Environment;
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
                'change_type' => $changelog->getChangeType(),
                'version_string' => $changelog->getVersionString(),
                'major_version' => $changelog->getMajorVersion(),
                'issue_number' => $changelog->getIssueNumber(),
                'tags' => $changelog->getTags(),
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
     * Retrieves changelogs based on search term, version, and type.
     * @param string|null $prompt The search term(s)
     * @param string|null $typo3Version The version (e.g., "14", "12.4", or "11.5.1")
     * @param string|null $changeType The category (e.g., "breaking", "feature")
     * @return array<int, array<string, mixed>> The scored and sorted search results
     */
    public function getChangelogs(?string $prompt, ?string $typo3Version = null, ?string $changeType = null): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $constraints = [];
        $words = [];

        if ($prompt !== null && $prompt !== '') {
            $words = array_filter(explode(' ', $prompt));
        }

        // 1. Fulltext search: Combine title and content matches with OR
        if ($words !== []) {
            $searchConditions = [];
            foreach ($words as $word) {
                $wordParam = $queryBuilder->createNamedParameter('%' . $word . '%');
                $searchConditions[] = $queryBuilder->expr()->like('title', $wordParam);
                $searchConditions[] = $queryBuilder->expr()->like('content', $wordParam);
            }
            $constraints[] = $queryBuilder->expr()->or(...$searchConditions);
        }

        // 2. Intelligent version filtering
        if ($typo3Version !== null && $typo3Version !== '') {
            if (!str_contains($typo3Version, '.')) {
                // Only major version provided (e.g., "14")
                $constraints[] = $queryBuilder->expr()->eq(
                    'major_version',
                    $queryBuilder->createNamedParameter((int)$typo3Version, Connection::PARAM_INT)
                );
            } else {
                // Specific version provided (e.g., "11.5.23" or "12.4")
                // We normalize to Major.Minor to ensure we find all related entries
                $parts = explode('.', $typo3Version);
                $normalizedVersion = $parts[0] . '.' . $parts[1];

                // Use LIKE to match "12.4" against "12.4.1", "12.4.2", etc.
                $constraints[] = $queryBuilder->expr()->like(
                    'version_string',
                    $queryBuilder->createNamedParameter($normalizedVersion . '%')
                );
            }
        }

        // 3. Category filter
        if ($changeType !== null && $changeType !== '') {
            $constraints[] = $queryBuilder->expr()->eq(
                'change_type',
                $queryBuilder->createNamedParameter($changeType)
            );
        }

        // Apply all constraints with AND
        if ($constraints !== []) {
            $queryBuilder->where($queryBuilder->expr()->and(...$constraints));
        }

        // 4. Scoring in SQL
        $scoreParts = [];
        $isFeatureIntent = false;
        $isDeprecationIntent = false;
        $isBreakingIntent = false;

        if ($prompt !== null && $prompt !== '') {
            $promptLower = mb_strtolower($prompt);
            $featureKeywords = ['implement', 'new', 'add', 'create', 'introduce', 'feature', 'introduction'];
            $deprecationKeywords = ['upgrade', 'deprecated', 'deprecation', 'migrate', 'migration', 'replace', 'replacement', 'obsolete'];
            $breakingKeywords = ['removed', 'removal', 'delete', 'deleted', 'breaking', 'broken', 'remove'];

            foreach ($featureKeywords as $keyword) {
                if (str_contains($promptLower, $keyword)) {
                    $isFeatureIntent = true;
                    break;
                }
            }
            foreach ($deprecationKeywords as $keyword) {
                if (str_contains($promptLower, $keyword)) {
                    $isDeprecationIntent = true;
                    break;
                }
            }
            foreach ($breakingKeywords as $keyword) {
                if (str_contains($promptLower, $keyword)) {
                    $isBreakingIntent = true;
                    break;
                }
            }
        }

        foreach ($words as $word) {
            $wordParam = $queryBuilder->createNamedParameter('%' . $word . '%');
            $scoreParts[] = 'CASE WHEN title LIKE ' . $wordParam . ' THEN 20 ELSE 0 END';
            $scoreParts[] = 'CASE WHEN content LIKE ' . $wordParam . ' THEN 5 ELSE 0 END';
        }

        if ($isFeatureIntent) {
            $scoreParts[] = "CASE WHEN change_type = 'Feature' THEN 50 ELSE 0 END";
        }
        if ($isDeprecationIntent) {
            $scoreParts[] = "CASE WHEN change_type = 'Deprecation' THEN 50 ELSE 0 END";
        }
        if ($isBreakingIntent) {
            $scoreParts[] = "CASE WHEN change_type = 'Breaking' THEN 50 ELSE 0 END";
        }

        $scoreExpression = $scoreParts !== [] ? '(' . implode(' + ', $scoreParts) . ')' : '0';

        $queryBuilder
            ->select('*')
            ->addSelectLiteral($scoreExpression . ' AS score')
            ->from(self::TABLE)
            ->orderBy('score', 'DESC')
            ->setMaxResults(50);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    public function getChangelogContentByUid(int $uid): ?string
    {
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('content')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
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
}
