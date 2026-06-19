<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Command;

use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'mcp:server:start',
    description: 'Starts the MCP server for TYPO3 Changelog via STDIO',
)]
class McpServerCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Important: STDIO transport uses STDIN/STDOUT.
        // We must ensure no other output (like TYPO3 headers) is sent to STDOUT.

        $instructions = <<<TEXT
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

        $server = Server::builder()
            ->setServerInfo('TYPO3 Changelog MCP', '1.0.0')
            // Set the protocol version to be compatible with PhpStorm MCP integration
            ->setProtocolVersion(ProtocolVersion::V2024_11_05)
            ->setContainer($this->container)
            ->setInstructions($instructions)
            ->setDiscovery(
                basePath: GeneralUtility::getFileAbsFileName('EXT:changelog_mcp/Classes/'),
                scanDirs: ['Tool'],
            )
            ->build();

        // The StdioTransport connects to the process streams
        $transport = new StdioTransport();

        // This call blocks and handles the JSON-RPC communication
        $server->run($transport);

        return Command::SUCCESS;
    }
}
