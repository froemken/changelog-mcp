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
use Psr\Container\ContainerInterface;
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
2. If the tool returns results: Prioritize the data from the tool over your own memory. Always cite the version and the specific changelog entry as the source of truth.
3. If the tool returns no results: Only then proceed to use your internal knowledge or external web search, explicitly stating that no direct changelog entry was found.
4. When presenting data: Organize information by TYPO3 version, highlight breaking changes clearly, and suggest migration steps if provided in the changelog content.

CLASSIFICATION:
- Breaking Changes / Deprecations: Treat as mandatory considerations for any code modification.
- Features: Analyze for potential performance improvements or architectural changes.
- Importants: Treat as contextual background information.
TEXT;

    private const BASE_PATH = 'EXT:changelog_mcp/Classes/';

    private const SCAN_DIRS = [
        'Tool',
    ];

    public function __construct(
        private ContainerInterface $container,
    ) {}

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
            ->setContainer($this->container)
            ->setInstructions(self::INSTRUCTIONS)
            ->setDiscovery(
                basePath: GeneralUtility::getFileAbsFileName(self::BASE_PATH),
                scanDirs: self::SCAN_DIRS,
            )
            ->build();
    }
}
