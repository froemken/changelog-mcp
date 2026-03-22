<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Domain\Repository;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ChangelogRepository
{
    private const CHANGELOG_DIRECTORY = 'EXT:core/Documentation/Changelog/';

    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    /**
     * Returns just the directory names like 10.4, 11.2, and 13.4.x
     */
    public function getTypo3VersionDirectories(): array
    {
        $directories = GeneralUtility::get_dirs(
            GeneralUtility::getFileAbsFileName(self::CHANGELOG_DIRECTORY),
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

    /**
     * Needed to convert rst files to Markdown
     */
    public function getAllChangelogFiles(): array
    {
        return GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            GeneralUtility::getFileAbsFileName(self::CHANGELOG_DIRECTORY),
            'rst',
            false,
            2,
            '(Howto.rst)',
        );
    }

    private function getChangelogFiles(string $type, string $version): array
    {
        $changelogFiles = [];
        foreach ($this->getChangelogDirectoriesForVersion($version) as $directory) {
            foreach (GeneralUtility::getFilesInDir($directory, 'rst') as $file) {
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
                    $foundDirectories[] = GeneralUtility::getFileAbsFileName(self::CHANGELOG_DIRECTORY . $availableDirectory);
                    break;
                }
            }
        }

        // Case 2: Version like "11" or if major.minor was not found, but a major version is given
        if ($foundDirectories === [] && $majorVersion !== '') {
            foreach ($availableDirectories as $availableDirectory) {
                if (str_starts_with($availableDirectory, $majorVersion . '.')) {
                    $foundDirectories[] = GeneralUtility::getFileAbsFileName(self::CHANGELOG_DIRECTORY . $availableDirectory);
                }
            }
        }

        return array_unique($foundDirectories);
    }
}
