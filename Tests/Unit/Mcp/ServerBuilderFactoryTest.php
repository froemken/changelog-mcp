<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Mcp;

use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use StefanFroemken\ChangelogMcp\Mcp\ServerBuilderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ServerBuilderFactoryTest extends UnitTestCase
{
    #[Test]
    public function createServerReturnsConfiguredServerInstance(): void
    {
        $containerMock = self::createStub(ContainerInterface::class);
        GeneralUtility::setContainer($containerMock);

        $factory = new ServerBuilderFactory();
        $server = $factory->createServer(ProtocolVersion::V2024_11_05);

        self::assertInstanceOf(Server::class, $server);
    }
}
