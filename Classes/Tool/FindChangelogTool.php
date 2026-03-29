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
     * Search the TYPO3 changelog database for specific topics.
     */
    #[McpTool(
        name: 'search_typo3_changelogs',
        description: 'SEARCH TOOL: Find TYPO3 changelogs for new features, code examples, breaking changes, or missing/renamed classes (e.g. ReplaceFileController). Use this to research TYPO3 API changes and migration paths for versions 7 through 14.'
    )]
    public function searchChangelogs(
        #[Schema(description: 'The search term (e.g. "encryption", "ReplaceFileController", "TCA").')]
        string $query,

        #[Schema(description: 'Optional TYPO3 version, e.g. "14" or "12.4".')]
        string $version = '',

        #[Schema(description: 'Optional category, e.g. "breaking" or "feature".')]
        string $type = ''
    ): array {
        $this->logger->info(sprintf('Search: [Query: %s] [Version: %s]', $query, $version));

        // Wir wandeln leere Strings intern wieder in null um für das Repository
        $versionParam = $version === '' ? null : $version;
        $typeParam = $type === '' ? null : $type;

        $searchResults = $this->changelogRepository->getChangelogs($query, $versionParam, $typeParam);

        if (empty($searchResults)) {
            return [
                'content' => [['type' => 'text', 'text' => "No results for '$query'."]],
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
                ['type' => 'text', 'text' => "Found UIDs. Use get_typo3_changelog_content(uid) now:\n\n" . implode("\n\n---\n\n", $output)]
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
