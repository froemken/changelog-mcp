<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Reaction;

use Mcp\Server;
use Mcp\Server\Protocol;
use Mcp\Server\Transport\TransportInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use StefanFroemken\ChangelogMcp\Mcp\ServerBuilderFactory;
use StefanFroemken\ChangelogMcp\Reaction\ChangelogMcpReaction;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ChangelogMcpReactionTest extends UnitTestCase
{
    #[Test]
    public function staticMetadataMethodsReturnConfiguredStrings(): void
    {
        self::assertSame('changelog-mcp', ChangelogMcpReaction::getType());
        self::assertSame('changelog_mcp.reaction:description', ChangelogMcpReaction::getDescription());
        self::assertSame('module-install-environment', ChangelogMcpReaction::getIconIdentifier());
    }

    #[Test]
    public function reactHandlesPostRequestAndRunsServer(): void
    {
        $factoryMock = $this->createMock(ServerBuilderFactory::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $protocolMock = $this->createMock(Protocol::class);
        $server = new Server($protocolMock);

        $factoryMock->expects($this->once())
            ->method('createServer')
            ->willReturn($server);

        $expectedResponse = new Response();

        $transportMock = $this->createMock(TransportInterface::class);
        $transportMock->expects($this->once())->method('initialize');
        $transportMock->expects($this->once())->method('listen')->willReturn($expectedResponse);
        $transportMock->expects($this->once())->method('close');

        $normalizedParamsMock = $this->createMock(NormalizedParams::class);
        $normalizedParamsMock->method('getHttpHost')->willReturn('localhost');

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects($this->once())->method('rewind');

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->method('getMethod')->willReturn('POST');
        $requestMock->method('getBody')->willReturn($streamMock);
        $requestMock->method('getQueryParams')->willReturn([]);
        $requestMock->method('getAttribute')->willReturnMap([
            ['normalizedParams', null, $normalizedParamsMock],
        ]);

        $reactionInstruction = new ReactionInstruction([
            'uid' => 1,
            'identifier' => 'reaction-instruction',
            'name' => 'Test Reaction',
            'reaction_type' => 'changelog-mcp',
        ]);

        $reaction = new class ($factoryMock, $loggerMock, $transportMock) extends ChangelogMcpReaction {
            /**
             * @param TransportInterface<mixed> $mockTransport
             */
            public function __construct(
                ServerBuilderFactory $serverBuilderFactory,
                LoggerInterface $logger,
                private readonly TransportInterface $mockTransport,
            ) {
                parent::__construct($serverBuilderFactory, $logger);
            }

            /**
             * @return TransportInterface<mixed>
             */
            protected function createHttpTransport(ServerRequestInterface $request): TransportInterface
            {
                return $this->mockTransport;
            }
        };

        $response = $reaction->react($requestMock, [], $reactionInstruction);

        self::assertSame($expectedResponse, $response);
    }
}
