<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Mcp\Tool;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Psr\Log\LoggerInterface;
use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use StefanFroemken\ChangelogMcp\Mcp\Tool\CompletionProvider\Typo3VersionCompletionProvider;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Note: This class is instantiated via MCP reflection using getInstance().
 * Dependency injection is not available in this context, so the TYPO3 logger
 * is created manually via GeneralUtility.
 */
final readonly class FindChangelogTool
{
    private LoggerInterface $logger;

    public function __construct(
        private ChangelogRepository $changelogRepository,
    ) {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Search tool for TYPO3 core changelogs, features, deprecations, breaking changes, and importants.
     * This tool provides direct access to the official TYPO3 Core changelog documentation for API modifications, removals, upgrades, and migrations.
     * You MUST call this tool as the first step before performing any web search or relying on pre-trained knowledge
     * whenever the user asks about TYPO3 core APIs, deprecations, features, breaking changes, or importants.
     */
    #[McpTool(name: 'search_changelogs')]
    public function search(
        #[Schema(description: 'Search for changelogs. ALWAYS use the results to identify the correct UID, then IMMEDIATELY fetch the full content using the show_changelog tool with the UID for details and migration instructions. Never provide advice based solely on titles.')] string $query = '',
        #[CompletionProvider(provider: Typo3VersionCompletionProvider::class)] #[Schema(description: 'Target TYPO3 version to upgrade to or target (e.g. "10", "11.5", "12.4", "13", "14"). The search will return matching changelogs up to this version.')] ?string $version = null,
        #[CompletionProvider(enum: ChangelogEnum::class)]
        #[Schema(description: 'Filter by TYPO3 change type. "breaking" (critical), "deprecation" (critical), "feature" (critical), or "important" (informational).')] ?string $type = null,
    ): CallToolResult {
        $this->logger->info(sprintf('Search for Changelogs: [Query: %s] [Version: %s]', $query, $version));

        $versionParam = $version === '' ? null : $version;
        $queryParam = $query === '' ? null : $query;

        $searchResults = $this->changelogRepository->getChangelogs($queryParam, $versionParam, $type);

        $text = 'I found ' . count($searchResults) . ' matching changelogs:' . PHP_EOL . PHP_EOL;
        foreach ($searchResults as $searchResult) {
            $text .= sprintf(
                '- [%s] %s (URI: typo3://changelog/%d, Type: %s, Version: %s)' . PHP_EOL,
                $searchResult['change_type'],
                $searchResult['title'],
                $searchResult['uid'],
                $searchResult['change_type'],
                $searchResult['version_string'],
            );
            if (!empty($searchResult['summary'])) {
                $text .= '  Summary: ' . str_replace("\n", "\n  ", trim($searchResult['summary'])) . PHP_EOL . PHP_EOL;
            }
        }

        $content = [
            new TextContent($text),
        ];

        foreach ($searchResults as $searchResult) {
            $content[] = EmbeddedResource::fromText(
                uri: 'typo3://changelog/' . $searchResult['uid'],
                text: $searchResult['title'],
                mimeType: 'text/markdown',
            );
        }

        return new CallToolResult($content);
    }

    /**
     * Retrieve the complete content of a specific TYPO3 changelog entry by its UID.
     * Always call this tool to read the details, migration steps, and code examples of a changelog.
     */
    #[McpTool(name: 'show_changelog')]
    public function showChangelog(
        #[Schema(description: 'The UID of the changelog entry (e.g. 197).')] int $uid,
    ): CallToolResult {
        $content = $this->changelogRepository->getChangelogContentByUid($uid);

        if (!$content) {
            return new CallToolResult([
                new TextContent('Changelog with ID ' . $uid . ' not found.'),
            ], isError: true);
        }

        return new CallToolResult([
            new TextContent($content),
        ]);
    }
}
