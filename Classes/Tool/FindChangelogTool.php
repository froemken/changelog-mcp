<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Psr\Log\LoggerInterface;
use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FindChangelogTool
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ChangelogRepository $changelogRepository,
    ) {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Search the TYPO3 changelog database for new features.
     */
    #[McpTool(
        name: 'search_typo3_features',
        description: 'Provides access to TYPO3 feature changelogs. Use this to identify new APIs, Core functionalities, and site handling improvements introduced in specific TYPO3 versions.'
    )]
    public function findFeatureChangelogs(
        #[Schema(description: 'Optional search term. Leave empty to list all features for a version.')]
        string $query = '',

        #[Schema(description: 'Optional TYPO3 version (e.g. "13", "12.4"). Leave empty to search all versions.')]
        string $version = ''
    ): array {
        $this->logger->info(sprintf('Find Feature Changelogs: [Query: %s] [Version: %s]', $query, $version));

        $versionParam = $version === '' ? null : $version;
        $queryParam = $query === '' ? null : $query;

        $searchResults = $this->changelogRepository->getChangelogs($queryParam, $versionParam, 'feature');

        if (empty($searchResults)) {
            return [
                'content' => [['type' => 'text', 'text' => "No feature changelogs found for '$query' in version '$version'."]],
            ];
        }

        return $this->formatSearchResults($searchResults, 'feature');
    }

    /**
     * Search the TYPO3 changelog database for deprecations and migration paths.
     */
    #[McpTool(
        name: 'search_typo3_deprecations',
        description: 'Queries the TYPO3 database for deprecated PHP classes, methods, and configurations. Returns mandatory migration paths and replacement suggestions for specific versions.'
    )]
    public function searchDeprecationChangelogs(
        #[Schema(description: 'Optional search term (e.g. "Fluid ViewHelper"). Leave empty to list all.')]
        string $query = '',

        #[Schema(description: 'Optional TYPO3 version (e.g. "11"). Leave empty to search all versions.')]
        string $version = ''
    ): array {
        $this->logger->info(sprintf('Find Deprecation Changelogs: [Query: %s] [Version: %s]', $query, $version));

        $versionParam = $version === '' ? null : $version;
        $queryParam = $query === '' ? null : $query;

        $searchResults = $this->changelogRepository->getChangelogs($queryParam, $versionParam, 'deprecation');

        if (empty($searchResults)) {
            return [
                'content' => [['type' => 'text', 'text' => "No deprecation changelogs found for '$query' in version '$version'."]],
            ];
        }

        return $this->formatSearchResults($searchResults, 'deprecation');
    }

    /**
     * Search the TYPO3 changelog database for breaking changes.
     */
    #[McpTool(
        name: 'search_typo3_breaking_changes',
        description: 'Queries the TYPO3 database for breaking changes, including removed APIs, deleted files, and backward-incompatible logic changes.'
    )]
    public function searchBreakingChangelogs(
        #[Schema(description: 'Optional search term. Leave empty to list all breaking changes for a version.')]
        string $query = '',

        #[Schema(description: 'Optional TYPO3 version (e.g. "14"). Leave empty to search all versions.')]
        string $version = ''
    ): array {
        $this->logger->info(sprintf('Find Breaking Changelogs: [Query: %s] [Version: %s]', $query, $version));

        $versionParam = $version === '' ? null : $version;
        $queryParam = $query === '' ? null : $query;

        $searchResults = $this->changelogRepository->getChangelogs($queryParam, $versionParam, 'breaking');

        if (empty($searchResults)) {
            return [
                'content' => [['type' => 'text', 'text' => "No breaking changelogs found for '$query' in version '$version'."]],
            ];
        }

        return $this->formatSearchResults($searchResults, 'breaking change');
    }

    /**
     * Retrieves the full content of a specific TYPO3 changelog file.
     */
    #[McpTool(
        name: 'get_typo3_changelog_details',
        description: 'Retrieves the full technical documentation, upgrade instructions, and PHP code examples for a specific TYPO3 change UID.',
    )]
    public function getChangelogContentTool(
        #[Schema(description: 'The UID of the changelog entry.')]
        int $uid,
    ): array {
        $this->logger->info(sprintf('Fetching full content for TYPO3 changelog UID: %d', $uid));

        $content = $this->changelogRepository->getChangelogContentByUid($uid);

        if ($content === null) {
            $this->logger->error(sprintf('Changelog UID %d not found in database.', $uid));
            return [
                'content' => [['type' => 'text', 'text' => "Error: Changelog entry with UID {$uid} could not be found."]],
                'isError' => true,
            ];
        }

        return [
            'content' => [['type' => 'text', 'text' => $content]],
        ];
    }

    /**
     * Helper to centralize formatting and keep the KI focused on the next step.
     */
    private function formatSearchResults(array $searchResults, string $typeLabel): array
    {
        $output = [];
        foreach (array_slice($searchResults, 0, 10) as $result) {
            $output[] = sprintf(
                "UID: %d | %s (v%s)\nAbstract: %s",
                $result['uid'],
                $result['title'],
                $result['version_string'],
                mb_strimwidth($result['content'], 0, 400, '...')
            );
        }

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => "Found {$typeLabel} entries. To see the full implementation or migration path, call get_typo3_changelog_details(uid) with the desired UID:\n\n" . implode("\n\n---\n\n", $output)
                ]
            ],
        ];
    }
}
