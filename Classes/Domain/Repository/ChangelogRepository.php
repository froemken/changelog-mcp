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

    public function create(Changelog $changelog, string $content): void
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
                'content' => $content,
            ],
        );
    }

    /**
     * Returns just the directory names like 10.4, 11.2, and 13.4.x
     */
    public function getTypo3VersionDirectories(): array
    {
        $directories = GeneralUtility::get_dirs(
            GeneralUtility::getFileAbsFileName(self::ORIGINAL_TYPO3_CHANGELOG_DIRECTORY),
        );

        if ($directories === null) {
            $this->logger->error('Given changelog directory is empty');
            $this->logger->error('Could not get directories from changelog directory');
            return [];
        }

        if (is_string($directories)) {
            $this->logger->error('Could not get directories from changelog directory');
            return [];
        }

        return $directories;
    }

    public function getBreakingFiles(string $version): array
    {
        return $this->getChangelogFiles('Breaking', $version);
    }

    public function getDeprecationFiles(string $version): array
    {
        return $this->getChangelogFiles('Deprecation', $version);
    }

    public function getFeatureFiles(string $version): array
    {
        return $this->getChangelogFiles('Feature', $version);
    }

    public function getImportantFiles(string $version): array
    {
        return $this->getChangelogFiles('Important', $version);
    }

    private function getChangelogFiles(string $type, string $version): array
    {
        $changelogFiles = [];
        foreach ($this->getChangelogDirectoriesForVersion($version) as $directory) {
            foreach (GeneralUtility::getFilesInDir($directory, 'md') as $file) {
                if (str_starts_with(basename($file), $type)) {
                    $changelogFiles[] = $directory . '/' . $file;
                }
            }
        }

        return $changelogFiles;
    }

    /**
     * Determines the appropriate changelog directory (or directories) based on the given TYPO3 version.
     *
     * @param string $version The TYPO3 version string (e.g., "11.5.23", "11.5", "11").
     * @return string[] An array of absolute paths to the changelog directories.
     */
    private function getChangelogDirectoriesForVersion(string $version): array
    {
        $availableDirectories = $this->getTypo3VersionDirectories();
        $foundDirectories = [];

        $versionParts = GeneralUtility::intExplode('.', $version, true);
        $majorMinorVersion = implode('.', array_slice($versionParts, 0, 2));
        $majorVersion = (string)($versionParts[0] ?? '');

        // Case 1: Version like "11.5.23" or "11.5" -> look for "11.5"
        if (count($versionParts) >= 2) {
            foreach ($availableDirectories as $availableDirectory) {
                if ($availableDirectory === $majorMinorVersion) {
                    $foundDirectories[] = GeneralUtility::getFileAbsFileName(
                        Environment::getVarPath() . '/prepared_changelogs/' . $availableDirectory,
                    );
                    break;
                }
            }
        }

        // Case 2: Version like "11" or if major.minor was not found, but a major version is given
        if ($foundDirectories === [] && $majorVersion !== '') {
            foreach ($availableDirectories as $availableDirectory) {
                if (str_starts_with($availableDirectory, $majorVersion . '.')) {
                    $foundDirectories[] = GeneralUtility::getFileAbsFileName(
                        Environment::getVarPath() . '/prepared_changelogs/' . $availableDirectory,
                    );
                }
            }
        }

        return array_unique($foundDirectories);
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
