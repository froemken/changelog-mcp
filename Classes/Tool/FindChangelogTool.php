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
     * Finds TYPO3 upgrade instructions, deprecations, and breaking changes.
     * * IMPORTANT: The query MUST include:
     * 1. A version string prefixed with "TYPO3" (e.g., "TYPO3 12", "TYPO3 13.4").
     * 2. At least one keyword: "breaking", "feature", "important", or "deprecated".
     * * Without both a version and a keyword, the result will be empty.
     * The AI should derive the version from project files (e.g., composer.json, AGENTS.md, GEMINI.md)
     * or ask the user if it is missing.
     *
     * @param string $query Search terms including a version and mandatory keywords.
     * @return string List of max 10 entries with absPath, search score, and 150-character abstract.
     */
    #[McpTool(
        name: 'find_typo3_changelog',
        description: 'Search for TYPO3 changelogs. REQUIRED: Must include "TYPO3 [version]" AND at least one keyword (breaking, feature, important, deprecated).'
    )]
    public function listChangelogs(
        #[Schema(
            description: 'Search query. REQUIRED: "TYPO3 [version]" AND keywords (breaking|feature|important|deprecated). Example: "TYPO3 12 breaking TCA"',
            minLength: 10
        )]
        string $query = ''
    ): string {
        $originalQuery = strtolower($query);
        $this->logger->info('Original Query: ' . $originalQuery);

        $typo3Version = null;
        if (preg_match('/typo3\s+(?P<version>\d+(\.\d+)?(\.\d+)?)/', $originalQuery, $matches)) {
            $typo3Version = $matches['version'];
            $query = str_replace($matches[0], '', $query); // Remove "typo3 X.Y" from the query
        }

        $changeType = null;
        $changeTypes = ['breaking', 'feature', 'important', 'deprecated'];
        foreach ($changeTypes as $type) {
            if (str_contains($originalQuery, $type)) {
                $changeType = $type;
                $query = str_replace($type, '', $query);
                break;
            }
        }

        // The remaining part of the query is the prompt for the database search
        $prompt = trim($query);

        $this->logger->info(
            sprintf(
                'Searching with: TYPO3 Version=%s, Change Type=%s, Prompt=%s',
                $typo3Version ?? 'N/A',
                $changeType ?? 'N/A',
                $prompt,
            )
        );

        $searchResults = $this->changelogRepository->getChangelogs($prompt, $typo3Version, $changeType);

        if ($searchResults === []) {
            $this->logger->info("No matching changelogs found for the query: '$originalQuery'.");
            return "No matching changelogs found for your query.";
        }

        // Limit the results to the top 10 to save tokens and keep the AI focused
        $topResults = array_slice($searchResults, 0, 10);

        $outputStrings = [];
        foreach ($topResults as $result) {
            $abstract = $result['content'];
            if (mb_strlen($abstract) > 150) {
                $abstract = mb_substr($abstract, 0, 150);
                $lastSpace = mb_strrpos($abstract, ' ');
                if ($lastSpace !== false) {
                    $abstract = mb_substr($abstract, 0, $lastSpace);
                }
                $abstract .= '...';
            }

            $outputStrings[] = sprintf(
                "UID: %d\nTitle: %s\nVersion: %s\nChange Type: %s\nRelevance Score: %d\nAbstract: %s",
                $result['uid'],
                $result['title'],
                $result['version_string'],
                $result['change_type'],
                $result['score'],
                $abstract,
            );
        }

        return "I found the following relevant changelogs, sorted by search relevance:\n\n" . implode("\n\n---\n\n", $outputStrings);
    }

    /**
     * Retrieves the content of a specific TYPO3 changelog file.
     *
     * @param int $uid The UID of the changelog entry.
     * @return string The content of the changelog entry, or an error message if not found.
     */
    #[McpTool(
        name: 'get_typo3_changelog_content',
        description: 'Retrieves the full content of a specific TYPO3 changelog entry given its UID. Use this tool after finding a relevant changelog entry with find_typo3_changelog.'
    )]
    public function getChangelogContentTool(int $uid): string
    {
        $content = $this->changelogRepository->getChangelogContentByUid($uid);
        if ($content === null) {
            return "Error: Changelog entry with UID '{$uid}' not found.";
        }

        return $content;
    }
}
