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
            $rawWords = array_filter(explode(' ', $prompt));
            $stopwords = [
                'how', 'to', 'the', 'a', 'an', 'of', 'in', 'and', 'is', 'for', 'with', 'on', 'as', 'by', 'at', 'it', 'from',
                'what', 'why', 'where', 'when', 'who', 'which', 'do', 'does', 'did', 'have', 'has', 'had', 'are', 'was', 'were',
                'correctly', 'write', 'about', 'use', 'using', 'get', 'set', 'make', 'create',
            ];
            $nonSelective = ['typo3', 'cms'];

            $cleanedWords = [];
            foreach ($rawWords as $word) {
                $wordLower = mb_strtolower($word);
                if (!in_array($wordLower, $stopwords, true)) {
                    $cleanedWords[] = $word;
                }
            }

            $finalWords = [];
            foreach ($cleanedWords as $word) {
                $wordLower = mb_strtolower($word);
                if (in_array($wordLower, $nonSelective, true)) {
                    continue;
                }
                $finalWords[] = $word;
            }

            if ($finalWords !== []) {
                $words = $finalWords;
            } elseif ($cleanedWords !== []) {
                $words = $cleanedWords;
            } else {
                $words = $rawWords;
            }
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
            $targetParts = explode('.', $typo3Version);
            $targetMajor = (int)$targetParts[0];

            if ($prompt !== null && $prompt !== '') {
                // For keyword searches, allow any version less than or equal to the target major version.
                // Further precise filtering (down to minor/patch) is done in PHP after retrieving records.
                $constraints[] = $queryBuilder->expr()->lte(
                    'major_version',
                    $queryBuilder->createNamedParameter($targetMajor, Connection::PARAM_INT),
                );
            } else {
                // For non-search listings, match the requested version strictly.
                if (count($targetParts) === 1) {
                    $constraints[] = $queryBuilder->expr()->eq(
                        'major_version',
                        $queryBuilder->createNamedParameter($targetMajor, Connection::PARAM_INT),
                    );
                } else {
                    $normalizedVersion = $targetParts[0] . '.' . $targetParts[1];
                    $constraints[] = $queryBuilder->expr()->like(
                        'version_string',
                        $queryBuilder->createNamedParameter($normalizedVersion . '%'),
                    );
                }
            }
        }

        // 3. Category filter
        if ($changeType !== null && $changeType !== '') {
            $constraints[] = $queryBuilder->expr()->eq(
                'change_type',
                $queryBuilder->createNamedParameter($changeType),
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
            ->setMaxResults($prompt !== null && $prompt !== '' ? 100 : 50);

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        if ($prompt !== null && $prompt !== '' && $typo3Version !== null && $typo3Version !== '') {
            $filteredResults = [];
            foreach ($results as $result) {
                if ($this->isVersionCompatible($result['version_string'], $typo3Version)) {
                    $filteredResults[] = $result;
                }
            }
            $results = $filteredResults;
        }

        $maxScore = 0;
        foreach ($results as $result) {
            $score = (int)($result['score'] ?? 0);
            if ($score > $maxScore) {
                $maxScore = $score;
            }
        }

        if ($maxScore >= 20) {
            $finalResults = [];
            foreach ($results as $result) {
                if ((int)($result['score'] ?? 0) >= 20) {
                    $finalResults[] = $result;
                }
            }
            return array_slice($finalResults, 0, 25);
        }

        return array_slice($results, 0, 10);
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

    private function isVersionCompatible(string $dbVersion, string $targetVersion): bool
    {
        $dbParts = explode('.', $dbVersion);
        $targetParts = explode('.', $targetVersion);

        $dbMajor = (int)($dbParts[0] ?? 0);
        $dbMinor = isset($dbParts[1]) ? (int)$dbParts[1] : 0;
        $dbPatch = isset($dbParts[2]) ? (int)$dbParts[2] : 0;

        $targetMajor = (int)($targetParts[0] ?? 0);

        if (count($targetParts) === 1) {
            return $dbMajor <= $targetMajor;
        }

        $targetMinor = (int)$targetParts[1];
        if (count($targetParts) === 2) {
            if ($dbMajor < $targetMajor) {
                return true;
            }
            return $dbMajor === $targetMajor && $dbMinor <= $targetMinor;
        }

        $targetPatch = (int)$targetParts[2];
        if ($dbMajor < $targetMajor) {
            return true;
        }
        if ($dbMajor === $targetMajor) {
            if ($dbMinor < $targetMinor) {
                return true;
            }
            return $dbMinor === $targetMinor && $dbPatch <= $targetPatch;
        }

        return false;
    }
}
