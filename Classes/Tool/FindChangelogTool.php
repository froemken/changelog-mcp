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
        // This class is called by a foreign composer package, which does not have access to TYPO3
        // dependencies and instantiates classes via "new", so LoggerAwareTrait is also not working.
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Search the TYPO3 changelog database for new features.
     */
    #[McpTool(
        name: 'find_typo3_feature_changelogs',
        description: 'SEARCH TOOL: Find TYPO3 changelogs specifically for new features. Use this to discover new functionalities in TYPO3 versions, e.g., for "What\'s new in TYPO3 14" or "TYPO3 14.1 features".'
    )]
    public function findFeatureChangelogs(
        #[Schema(description: 'Optional search term (e.g. "new API", "site handling").')]
        string $query = '',

        #[Schema(description: 'Optional TYPO3 version, e.g. "14", "14.1", or "13". Defaults to an empty string to search all versions.')]
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

        $output = [];
        foreach (array_slice($searchResults, 0, 10) as $result) {
            $output[] = sprintf(
                "UID: %d | %s (v%s)\nAbstract: %s",
                $result['uid'],
                $result['title'],
                $result['version_string'],
                mb_strimwidth($result['content'], 0, 150, '...')
            );
        }

        return [
            'content' => [
                ['type' => 'text', 'text' => "Found feature UIDs. Use get_typo3_changelog_content(uid) now:\n\n" . implode("\n\n---\n\n", $output)]
            ],
        ];
    }

    /**
     * Search the TYPO3 changelog database for deprecations and migration paths.
     */
    #[McpTool(
        name: 'find_typo3_deprecation_changelogs',
        description: 'SEARCH TOOL: Find TYPO3 changelogs specifically for deprecations. Use this to identify deprecated code snippets and find their migration paths to newer TYPO3 versions, e.g., for "TYPO3 12 deprecations" or "migrate Extbase query".'
    )]
    public function findDeprecationChangelogs(
        #[Schema(description: 'Optional search term (e.g. "Extbase query", "Fluid ViewHelper").')]
        string $query = '',

        #[Schema(description: 'Optional TYPO3 version, e.g. "14", "14.1", or "13". Defaults to an empty string to search all versions.')]
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

        $output = [];
        foreach (array_slice($searchResults, 0, 10) as $result) {
            $output[] = sprintf(
                "UID: %d | %s (v%s)\nAbstract: %s",
                $result['uid'],
                $result['title'],
                $result['version_string'],
                mb_strimwidth($result['content'], 0, 150, '...')
            );
        }

        return [
            'content' => [
                ['type' => 'text', 'text' => "Found deprecation UIDs. Use get_typo3_changelog_content(uid) now:\n\n" . implode("\n\n---\n\n", $output)]
            ],
        ];
    }

    /**
     * Search the TYPO3 changelog database for breaking changes.
     */
    #[McpTool(
        name: 'find_typo3_breaking_changelogs',
        description: 'SEARCH TOOL: Find TYPO3 changelogs specifically for breaking changes. Use this to identify removed classes, methods, arguments, files, or functionalities in TYPO3 versions, e.g., for "TYPO3 12 breaking changes" or "removed API".'
    )]
    public function findBreakingChangelogs(
        #[Schema(description: 'Optional search term (e.g. "removed class", "method signature change", "file deleted").')]
        string $query = '',

        #[Schema(description: 'Optional TYPO3 version, e.g. "14", "14.1", or "13". Defaults to an empty string to search all versions.')]
        string $version = ''
    ): array {
        $this->logger->info(sprintf('Find Breaking Changelogs: [Query: %s] [Version: %s]', $query, $version));

        $versionParam = $version === '' ? null : $version;
        $queryParam = $query === '' ? null : $query;

        // Assuming 'breaking' is a valid type for getChangelogs in ChangelogRepository
        $searchResults = $this->changelogRepository->getChangelogs($queryParam, $versionParam, 'breaking');

        if (empty($searchResults)) {
            return [
                'content' => [['type' => 'text', 'text' => "No breaking changelogs found for '$query' in version '$version'."]],
            ];
        }

        $output = [];
        foreach (array_slice($searchResults, 0, 10) as $result) {
            $output[] = sprintf(
                "UID: %d | %s (v%s)\nAbstract: %s",
                $result['uid'],
                $result['title'],
                $result['version_string'],
                mb_strimwidth($result['content'], 0, 150, '...')
            );
        }

        return [
            'content' => [
                ['type' => 'text', 'text' => "Found breaking change UIDs. Use get_typo3_changelog_content(uid) now:\n\n" . implode("\n\n---\n\n", $output)]
            ],
        ];
    }

    /**
     * Retrieves the full content of a specific TYPO3 changelog file.
     */
    #[McpTool(
        name: 'get_typo3_changelog_content',
        description: 'CONTENT TOOL: Retrieves the full technical documentation and official PHP code examples for a specific TYPO3 change. Use this to provide the user with exact implementation details found in the changelog.',
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
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Error: Changelog entry with UID {$uid} could not be found.",
                    ],
                ],
                'isError' => true,
            ];
        }

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $content,
                ],
            ],
        ];
    }
}
