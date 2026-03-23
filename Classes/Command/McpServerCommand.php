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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'mcp:server:start',
    description: 'Starts the MCP server for TYPO3 Changelog via STDIO'
)]
class McpServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Important: STDIO transport uses STDIN/STDOUT.
        // We must ensure no other output (like TYPO3 headers) is sent to STDOUT.

        $server = Server::builder()
            ->setServerInfo('TYPO3 Changelog MCP', '1.0.0')
            // Set the protocol version to be compatible with PhpStorm MCP integration
            ->setProtocolVersion(ProtocolVersion::V2024_11_05)
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
