<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tool;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Psr\Log\LoggerInterface;
use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use StefanFroemken\ChangelogMcp\Tool\CompletionProvider\Typo3VersionCompletionProvider;
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

    #[McpTool(name: 'search_changelogs', description: 'Search for TYPO3 upgrade logs, features, and deprecations.')]
    public function search(
        string $query = '',
        #[CompletionProvider(provider: Typo3VersionCompletionProvider::class)]
        string $version = '',
        ?ChangelogEnum $type = null
    ): array {
        $this->logger->info(sprintf('Search for Changelogs: [Query: %s] [Version: %s]', $query, $version));

        $versionParam = $version === '' ? null : $version;
        $queryParam = $query === '' ? null : $query;

        $searchResults = $this->changelogRepository->getChangelogs($queryParam, $versionParam, $type->value);

        return $this->formatSearchResults($searchResults, $type->value);
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
