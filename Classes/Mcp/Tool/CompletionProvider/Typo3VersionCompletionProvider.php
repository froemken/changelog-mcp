<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Mcp\Tool\CompletionProvider;

use Doctrine\DBAL\Exception;
use Mcp\Capability\Completion\ProviderInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class Typo3VersionCompletionProvider implements ProviderInterface
{
    private const TABLE = 'tx_changelogmcp_changelog';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getCompletions(?string $currentValue): array
    {
        if ($currentValue === null || $currentValue === '') {
            return [];
        }

        if (str_contains($currentValue, '.')) {
            $parts = explode('.', $currentValue);
            $normalizedVersion = $parts[0] . '.' . $parts[1];
            $majorVersion = $parts[0];
            $typo3VersionColumn = 'version_string';
        } else {
            $normalizedVersion = $currentValue;
            $majorVersion = $currentValue;
            $typo3VersionColumn = 'major_string';
        }

        $queryBuilder = $this->getQueryBuilder();
        try {
            $completions = $queryBuilder
                ->select('version_string')
                ->distinct()
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        $typo3VersionColumn,
                        $queryBuilder->createNamedParameter($normalizedVersion, Connection::PARAM_STR),
                    ),
                )
                ->executeQuery()
                ->fetchFirstColumn();

            array_unshift($completions, $majorVersion);

            return array_unique($completions);
        } catch (Exception) {
        }

        return [];
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
