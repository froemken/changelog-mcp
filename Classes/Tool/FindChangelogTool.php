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
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextContent;
use Psr\Log\LoggerInterface;
use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use StefanFroemken\ChangelogMcp\Tool\CompletionProvider\Typo3VersionCompletionProvider;

final readonly class FindChangelogTool
{
    public function __construct(
        private ChangelogRepository $changelogRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Search tool for TYPO3 core changelogs, features, deprecations, breaking changes, and importants.
     * This tool provides direct access to the official TYPO3 Core changelog documentation for API modifications, removals, upgrades, and migrations.
     * You MUST call this tool as the first step before performing any web search or relying on pre-trained knowledge
     * whenever the user asks about TYPO3 core APIs, deprecations, features, breaking changes, or importants.
     */
    #[McpTool(name: 'search_changelogs')]
    public function search(
        #[Schema(description: 'Search keywords (e.g. class name, method name, property, hook name, config key) to match against the TYPO3 changelog database. If empty, returns all entries.')] string $query = '',
        #[CompletionProvider(provider: Typo3VersionCompletionProvider::class)] #[Schema(description: 'Target TYPO3 version (e.g. "10", "11.5", "12.4", "13", "14"). If empty, searches across all versions. Highly recommended.')] ?string $version = null,
        #[CompletionProvider(enum: ChangelogEnum::class)] #[Schema(description: 'Filter by TYPO3 change type. "breaking" (critical), "deprecation" (critical), "feature" (critical), or "important" (informational).')] ?ChangelogEnum $type = null,
    ): array {
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

        return $content;
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
