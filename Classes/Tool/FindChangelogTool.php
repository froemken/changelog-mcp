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

final class FindChangelogTool
{
    public function __construct(
        private readonly ChangelogRepository $changelogRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Searches for TYPO3 changelogs, features, and deprecations.
     */
    #[McpTool(name: 'search_changelogs')]
    public function search(
        #[Schema(description: 'Keywords for full-text search. If empty, all entries for the version are returned.')]
        string $query = '',

        #[CompletionProvider(provider: Typo3VersionCompletionProvider::class)]
        #[Schema(description: 'The TYPO3 version (e.g., "12.4"). Leave empty to search across all versions.')]
        ?string $version = null,

        #[CompletionProvider(enum: ChangelogEnum::class)]
        #[Schema(description: 'Filter entries by their specific TYPO3 change type.')]
        ?ChangelogEnum $type = null,
    ): array {
        $this->logger->info(sprintf('Search for Changelogs: [Query: %s] [Version: %s]', $query, $version));

        $versionParam = $version === '' ? null : $version;
        $queryParam = $query === '' ? null : $query;

        $searchResults = $this->changelogRepository->getChangelogs($queryParam, $versionParam, $type->value);

        $content = [
            new TextContent('I found ' . count($searchResults) . ' matching changelogs:')
        ];

        foreach ($searchResults as $searchResult) {
            $content[] = EmbeddedResource::fromText(
                uri: "typo3://changelog/" . $searchResult['uid'],
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
