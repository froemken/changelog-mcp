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
You are connected to a community-provided TYPO3 Changelog MCP server.
This server indexes and provides direct access to the official TYPO3 Core changelogs, including:
1. **Breaking Changes** (High Priority) - Changes that remove or modify APIs, breaking backward compatibility.
2. **Deprecations** (High Priority) - APIs marked for future removal. Always check these when refactoring or preparing upgrades.
3. **Features** (High Priority) - New APIs, configuration options, or features introduced in TYPO3.
4. **Importants** (Normal Priority) - Important notes and informational updates that do not fall under breaking, deprecation, or feature.

CRITICAL INSTRUCTIONS:
- You MUST query this server FIRST whenever the user asks about TYPO3 APIs, deprecations, breaking changes, features, or upgrades.
- Do NOT rely on your pre-trained LLM knowledge for TYPO3 changelog information, as TYPO3 versions evolve rapidly and your knowledge may be outdated or incomplete.
- If the tool 'search_changelogs' returns no results, you are then permitted to use web search or external documentation to find the required information.
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
