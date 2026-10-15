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
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
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
        #[CompletionProvider(provider: Typo3VersionCompletionProvider::class)] #[Schema(description: 'Lower TYPO3 version boundary (e.g. "10"). Changelogs from this version onwards are included. To query exactly one version, set both fromVersion and toVersion to the same value (e.g. "14").')] ?string $fromVersion = null,
        #[CompletionProvider(provider: Typo3VersionCompletionProvider::class)] #[Schema(description: 'Upper TYPO3 version boundary / target version (e.g. "13" or "14"). Changelogs up to this version are included. To query exactly one version, set both fromVersion and toVersion to the same value (e.g. "14").')] ?string $toVersion = null,
        #[CompletionProvider(enum: ChangelogEnum::class)] #[Schema(description: 'Filter by TYPO3 change type. "breaking" (critical), "deprecation" (critical), "feature" (critical), or "important" (informational).')] ?string $type = null,
        #[Schema(description: 'Maximum number of changelogs to return (1-100, default 50).')] int $limit = 50,
        #[Schema(description: 'Offset for pagination (default 0).')] int $offset = 0,
    ): CallToolResult {
        $boundedLimit = max(1, min(100, $limit));
        $boundedOffset = max(0, $offset);
        $this->logger->info(sprintf(
            'Search for Changelogs: [Query: %s] [FromVersion: %s] [ToVersion: %s] [Type: %s] [Limit: %d] [Offset: %d]',
            $query,
            (string)$fromVersion,
            (string)$toVersion,
            (string)$type,
            $boundedLimit,
            $boundedOffset,
        ));

        $searchResults = $this->changelogRepository->getChangelogs(
            $query === '' ? null : $query,
            $fromVersion === '' ? null : $fromVersion,
            $toVersion === '' ? null : $toVersion,
            $type === '' ? null : $type,
            $boundedLimit,
            $boundedOffset,
        );

        return $this->buildCallToolResult($searchResults['totalCount'], $boundedOffset, $searchResults['items']);
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

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function buildCallToolResult(int $totalCount, int $offset, array $items): CallToolResult
    {
        $start = $items === [] ? 0 : $offset + 1;
        $end = $items === [] ? 0 : $offset + count($items);
        $text = sprintf('Found %d matching changelogs (showing %d-%d):', $totalCount, $start, $end) . PHP_EOL . PHP_EOL;

        foreach ($items as $item) {
            $text .= sprintf(
                '- [%s] %s (URI: typo3://changelog/%d, Type: %s, Version: %s)' . PHP_EOL,
                $item['change_type'],
                $item['title'],
                $item['uid'],
                $item['change_type'],
                $item['version_string'],
            );
            if (!empty($item['summary'])) {
                $text .= '  Summary: ' . str_replace("\n", "\n  ", trim((string)$item['summary'])) . PHP_EOL . PHP_EOL;
            }
        }

        $content = [new TextContent($text)];
        foreach ($items as $item) {
            $content[] = EmbeddedResource::fromText(
                uri: 'typo3://changelog/' . $item['uid'],
                text: (string)$item['title'],
                mimeType: 'text/markdown',
            );
        }

        return new CallToolResult($content);
    }
}
