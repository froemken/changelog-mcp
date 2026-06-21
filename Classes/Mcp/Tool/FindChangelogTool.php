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
use Mcp\Capability\Attribute\McpResourceTemplate;
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
        #[Schema(description: 'Search for changelogs. ALWAYS use the results to identify the correct UID, then IMMEDIATELY fetch the full content using the resource URI for any migration task. Never provide advice based solely on the list of titles.')] string $query = '',
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

    #[McpResourceTemplate(
        uriTemplate: 'typo3://changelog/{uid}',
        name: 'changelog_content',
        mimeType: 'text/markdown',
    )]
    public function getChangelogContent(string $uid): string
    {
        $content = $this->changelogRepository->getChangelogContentByUid((int)$uid);

        if (!$content) {
            throw new \Mcp\Exception\ResourceReadException('Changelog with ID ' . $uid . ' not found.');
        }

        return $content;
    }
}
