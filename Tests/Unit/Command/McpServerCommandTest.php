<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Command;

use Mcp\Server;
use Mcp\Server\Protocol;
use Mcp\Server\Transport\TransportInterface;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\Command\McpServerCommand;
use StefanFroemken\ChangelogMcp\Mcp\ServerBuilderFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class McpServerCommandTest extends UnitTestCase
{
    #[Test]
    public function executeRunsServerWithStdioTransport(): void
    {
        $factoryMock = $this->createMock(ServerBuilderFactory::class);
        $protocolMock = self::createStub(Protocol::class);
        $server = new Server($protocolMock);

        $factoryMock->expects($this->once())
            ->method('createServer')
            ->willReturn($server);

        $transportMock = $this->createMock(TransportInterface::class);
        $transportMock->expects($this->once())->method('initialize');
        $transportMock->expects($this->once())->method('listen')->willReturn(null);
        $transportMock->expects($this->once())->method('close');

        $command = new McpServerCommand($factoryMock, $transportMock);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }
}
