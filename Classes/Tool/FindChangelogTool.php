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
use StefanFroemken\ChangelogMcp\Service\Changelog;
use StefanFroemken\ChangelogMcp\Service\ChangelogService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FindChangelogTool
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ChangelogRepository $changelogRepository,
        private readonly ChangelogService $changelogService,
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
     * @param string $query Search terms including version and mandatory keywords.
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
        $query = strtolower($query);

        $this->logger->error('Query: ' . $query);

        $matches = [];
        if (!preg_match('/typo3 (?P<version>\d+(\.\d+)?(\.\d+)?)/', $query, $matches)) {
            $this->logger->error('No TYPO3 version given');
            return 'Error: No TYPO3 version given';
        }

        // Remove the context from the query
        $query = str_replace('typo3', '', $query);
        $query = str_replace($matches['version'], '', $query);

        if (str_contains($query, 'breaking')) {
            $files = $this->changelogRepository->getBreakingFiles($matches['version']);
            $query = str_replace('breaking', '', $query);
        } elseif(str_contains($query, 'deprecated')) {
            $files = $this->changelogRepository->getDeprecationFiles($matches['version']);
            $query = str_replace('deprecated', '', $query);
        } elseif(str_contains($query, 'feature')) {
            $files = $this->changelogRepository->getFeatureFiles($matches['version']);
            $query = str_replace('feature', '', $query);
        } elseif(str_contains($query, 'important')) {
            $files = $this->changelogRepository->getImportantFiles($matches['version']);
            $query = str_replace('important', '', $query);
        } else {
            $this->logger->error('No search type like breaking/deprecated/feature/important given');
            return 'Error: No search type like breaking/deprecated/feature/important given';
        }

        if ($files === []) {
            $this->logger->error('No related files for search type found');
            return 'Error: No related files for search type found';
        }

        $this->logger->error('Number of files found for analyzing: ' . count($files));

        $searchResults = [];

        foreach ($files as $absFile) {
            $changelog = $this->changelogService->getChangelog($absFile);

            if (!$changelog instanceof Changelog) {
                continue;
            }

            $data = $this->getMetadata($query, $changelog->getContent(), $changelog->getAbsFile());

            // Only include files that actually match the search
            //if ($data['score'] > 0) {
                $searchResults[] = $data;
            //}
        }

        $this->logger->error('Number of matching files for MCP result: ' . count($searchResults));

        // Sort the array by score in descending order
        usort($searchResults, function (array $a, array $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limit the results to the top 10 to save tokens and keep the AI focused
        $topResults = array_slice($searchResults, 0, 10);

        $outputStrings = [];
        foreach ($topResults as $result) {
            $outputStrings[] = sprintf(
                "File: %s\nRelevance Score: %d\nAbstract: %s",
                $result['file'],
                $result['score'],
                $result['abstract']
            );
        }

        if ($outputStrings === []) {
            $this->logger->error("No matching files found for the query: '$query'.");
            return "Error: No matching files found for the query: '$query'.";
        }

        return "I found the following relevant files, sorted by search relevance:\n\n" . implode("\n\n---\n\n", $outputStrings);
    }

    /**
     * Retrieves the content of a specific TYPO3 changelog file.
     *
     * @param string $absFile The absolute path to the changelog file.
     * @return string The cleaned content of the changelog file, or an error message if not found/readable.
     */
    #[McpTool(
        name: 'get_typo3_changelog_content',
        description: 'Retrieves the full content of a specific TYPO3 changelog file given its absolute path. Use this tool after finding a relevant changelog file with find_typo3_changelog.'
    )]
    public function getChangelogContentTool(string $absFile): string
    {
        $changelog = $this->changelogService->getChangelog($absFile);
        if ($changelog === null) {
            return "Error: Changelog file not found or not readable at '{$absFile}'.";
        }

        return $changelog->getContent();
    }

    private function getSearchScore(string $query, string $content): int
    {
        $score = 0;
        $keywords = GeneralUtility::trimExplode(' ', strtolower($query), true);
        $contentLower = strtolower($content);

        foreach ($keywords as $word) {
            // Count how many times the keyword appears
            $count = substr_count($contentLower, $word);

            if ($count > 0) {
                // Bonus points if the word is actually present
                $score += 10;
                // Add frequency to the score
                $score += $count;
            }
        }

        return $score;
    }

    private function getMetadata(string $query, string $content, string $absFile): array
    {
        return [
            'score' => $this->getSearchScore($query, $content),
            'file' => $absFile,
            'abstract' => $this->getAbstract($content),
        ];
    }

    private function getAbstract(string $content): string
    {
        $abstract = $content;

        if (mb_strlen($content) > 150) {
            // Cut to 150 chars
            $abstract = mb_substr($content, 0, 150);

            // Optional: Cut at the last space to avoid broken words
            $lastSpace = mb_strrpos($abstract, ' ');
            if ($lastSpace !== false) {
                $abstract = mb_substr($abstract, 0, $lastSpace);
            }

            $abstract .= '...';
        }

        return $abstract;
    }
}
