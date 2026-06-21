<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Command;

use Mcp\Server\Transport\StdioTransport;
use StefanFroemken\ChangelogMcp\Mcp\ServerBuilderFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'changelog:mcp:server',
    description: 'Starts the MCP server for TYPO3 Changelog via STDIO',
)]
class McpServerCommand extends Command
{
    public function __construct(
        private readonly ServerBuilderFactory $serverBuilderFactory,
    ) {
        parent::__construct();
    }

    /**
     * Important: STDIO transport uses STDIN/STDOUT.
     * We must ensure no other output (like TYPO3 headers) is sent to STDOUT.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $server = $this->serverBuilderFactory->createServer();
        $server->run(new StdioTransport());

        return Command::SUCCESS;
    }
}
