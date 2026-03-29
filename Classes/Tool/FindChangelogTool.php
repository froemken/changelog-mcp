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
     * Searches the TYPO3 changelog database for breaking changes, features, and deprecations.
     */
    #[McpTool(
        name: 'find_typo3_changelog',
        description: 'Search for TYPO3 upgrade instructions, breaking changes, or features. Best used with a specific version and type.'
    )]
    public function listChangelogs(
        #[Schema(
            description: 'The search term or topic (e.g., "TCA", "Fluid", "DataHandler").',
            minLength: 2,
        )]
        string $query,

        #[Schema(
            description: 'The TYPO3 version. Supports major (e.g., "12") or specific versions (e.g., "12.4").',
            pattern: '^\d+(\.\d+)*$',
        )]
        ?string $version = null,

        #[Schema(
            description: 'The category of the changelog entry.',
            enum: ['breaking', 'feature', 'important', 'deprecated'],
        )]
        ?string $type = null
    ): string {
        $this->logger->info(sprintf(
            'Executing find_typo3_changelog: [Query: %s] [Version: %s] [Type: %s]',
            $query,
            $version ?? 'any',
            $type ?? 'any',
        ));

        $searchResults = $this->changelogRepository->getChangelogs($query, $version, $type);

        if (empty($searchResults)) {
            return "No TYPO3 changelogs found matching your criteria ($query, $version, $type).";
        }

        $output = [];
        foreach (array_slice($searchResults, 0, 10) as $result) {
            $abstract = $result['content'];
            if (mb_strlen($abstract) > 150) {
                $abstract = mb_substr($abstract, 0, 150);
                $lastSpace = mb_strrpos($abstract, ' ');
                $abstract = ($lastSpace !== false) ? mb_substr($abstract, 0, $lastSpace) : $abstract;
                $abstract .= '...';
            }

            $output[] = sprintf(
                "UID: %d | %s (v%s)\nType: %s | Score: %d\nAbstract: %s",
                $result['uid'],
                $result['title'],
                $result['version_string'],
                $result['change_type'],
                $result['score'],
                $abstract,
            );
        }

        return "Relevant TYPO3 changelogs:\n\n" . implode("\n\n---\n\n", $output);
    }

    /**
     * Retrieves the full content of a specific TYPO3 changelog file.
     */
    #[McpTool(
        name: 'get_typo3_changelog_content',
        description: 'Returns the full content of a TYPO3 changelog entry. Use the UID obtained from find_typo3_changelog.'
    )]
    public function getChangelogContentTool(
        #[Schema(description: 'The unique identifier (UID) of the changelog entry.')]
        int $uid,
    ): string {
        $this->logger->info(sprintf('Fetching full content for TYPO3 changelog UID: %d', $uid));

        $content = $this->changelogRepository->getChangelogContentByUid($uid);

        if ($content === null) {
            $this->logger->error(sprintf('Changelog UID %d not found in database.', $uid));
            return "Error: Changelog entry with UID {$uid} could not be found.";
        }

        return $content;
    }
}
