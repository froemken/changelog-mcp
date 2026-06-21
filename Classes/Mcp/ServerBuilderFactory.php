<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Mcp;

use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Session\SessionStoreInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class ServerBuilderFactory
{
    private const INSTRUCTIONS = <<<TEXT
You are the primary interface for official TYPO3 Core changelogs, provided via the TYPO3 Changelog MCP server.

CORE RESPONSIBILITY:
- Your internal knowledge regarding TYPO3 APIs, deprecations, and breaking changes is considered secondary and potentially outdated.
- You MUST use the 'search_changelogs' tool as the authoritative source for any technical query related to TYPO3 development, refactoring, or version upgrades.
- NEVER guess or synthesize TYPO3-specific technical details from pre-trained memory if the changelog server can provide the source data.

WORKFLOW RULES:
1. Always query the 'search_changelogs' tool FIRST.
2. If the tool returns results: Prioritize the data from the tool over your own memory. Always identify the relevant changelog(s) by their UID and IMMEDIATELY fetch their full content using the 'show_changelog' tool to inspect code examples and detailed migration instructions.
3. NEVER tell the user to click the URI or read the resource themselves. Always fetch it using the 'show_changelog' tool and present the complete migration details directly to the user.
4. If the tool returns no results: Only then proceed to use your internal knowledge or external web search, explicitly stating that no direct changelog entry was found.
5. When presenting data: Organize information by TYPO3 version, highlight breaking changes clearly, and suggest migration steps if provided in the changelog content.

CLASSIFICATION:
- Breaking Changes / Deprecations: Treat as mandatory considerations for any code modification.
- Features: Analyze for potential performance improvements or architectural changes.
- Importants: Treat as contextual background information.
TEXT;

    private const BASE_PATH = 'EXT:changelog_mcp/Classes/';

    /**
     * Define the paths where to search for MCP PHP attributes
     */
    private const SCAN_DIRS = [
        'Mcp/Tool',
    ];

    private const SESSION_PATH = '/changelog_mcp_sessions';

    /**
     * Creates the TYPO3 Changelog MCP server.
     *
     * The protocol version defaults to the MCP server version that is compatible
     * with the PhpStorm MCP integration.
     */
    public function createServer(
        ProtocolVersion $protocolVersion = ProtocolVersion::V2024_11_05,
    ): Server {
        return Server::builder()
            ->setServerInfo('TYPO3 Changelog MCP', '1.0.0')
            ->setProtocolVersion($protocolVersion)
            ->setContainer(GeneralUtility::getContainer())
            ->setInstructions(self::INSTRUCTIONS)
            ->setSession($this->getSessionStorage())
            ->setDiscovery(
                basePath: GeneralUtility::getFileAbsFileName(self::BASE_PATH),
                scanDirs: self::SCAN_DIRS,
                namePatterns: ['*.php'],
            )
            ->build();
    }

    private function getSessionStorage(): SessionStoreInterface
    {
        GeneralUtility::mkdir_deep($this->getAbsSessionPath());

        return new FileSessionStore($this->getAbsSessionPath());
    }

    private function getAbsSessionPath(): string
    {
        return Environment::getVarPath() . self::SESSION_PATH;
    }
}
