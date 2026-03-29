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

    public function getChangelogs(string $prompt, ?string $typo3Version = null, ?string $changeType = null): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from(self::TABLE);

        $constraints = [];
        $words = array_filter(explode(' ', $prompt));

        if ($words !== []) {
            $searchConditions = [];
            foreach ($words as $word) {
                $searchConditions[] = $queryBuilder->expr()->like(
                    'title',
                    $queryBuilder->createNamedParameter('%' . $word . '%'),
                );
                $searchConditions[] = $queryBuilder->expr()->like(
                    'content',
                    $queryBuilder->createNamedParameter('%' . $word . '%'),
                );
            }
            $constraints[] = $queryBuilder->expr()->or(...$searchConditions);
        }

        if ($typo3Version !== null) {
            $constraints[] = $queryBuilder->expr()->eq('version_string', $queryBuilder->createNamedParameter($typo3Version));
        }

        if ($changeType !== null) {
            $constraints[] = $queryBuilder->expr()->eq('change_type', $queryBuilder->createNamedParameter($changeType));
        }

        if (!empty($constraints)) {
            $queryBuilder->where(...$constraints);
        }

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Scoring and sorting
        foreach ($results as &$result) {
            $score = 0;
            $matchedWordsCount = 0;

            foreach ($words as $word) {
                if (stripos($result['title'], $word) !== false) {
                    $score += 10; // Higher score for title matches
                    $matchedWordsCount++;
                } elseif (stripos($result['content'], $word) !== false) {
                    $score += 1;
                    $matchedWordsCount++;
                }
            }
            // Higher score for more matched words
            $score += $matchedWordsCount * 5;
            $result['score'] = $score;
        }

        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $results;
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
