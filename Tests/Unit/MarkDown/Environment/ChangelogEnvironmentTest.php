<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown\Environment;

use Doctrine\RST\Configuration;
use Doctrine\RST\References\ResolvedReference;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\Environment\ChangelogEnvironment;
use StefanFroemken\ChangelogMcp\MarkDown\Reference\GenericReference;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ChangelogEnvironmentTest extends UnitTestCase
{
    #[Test]
    public function resolveDynamicallyRegistersUnknownReference(): void
    {
        $configuration = new Configuration();
        // Abort on error is true by default, so if not registered, an exception would be thrown
        $configuration->abortOnError(true);
        $environment = new ChangelogEnvironment($configuration);

        $resolved = $environment->resolve('custom-unknown-section', 'my-data');

        self::assertInstanceOf(ResolvedReference::class, $resolved);
        self::assertSame('my-data', $resolved->getTitle());
        self::assertNull($resolved->getUrl());
        self::assertSame(['role' => 'custom-unknown-section'], $resolved->getAttributes());
    }

    #[Test]
    public function foundDynamicallyRegistersUnknownReference(): void
    {
        $configuration = new Configuration();
        $configuration->abortOnError(true);
        $environment = new ChangelogEnvironment($configuration);

        // Should dynamically register reference and not throw an exception
        $result = $environment->found('dynamic-section', 'some-data');
        self::assertNull($result);

        // Subsequent resolve should now resolve without needing to re-register
        $resolved = $environment->resolve('dynamic-section', 'some-data');
        self::assertInstanceOf(ResolvedReference::class, $resolved);
        self::assertSame('some-data', $resolved->getTitle());
    }

    #[Test]
    public function explicitlyRegisteredReferenceIsRespected(): void
    {
        $configuration = new Configuration();
        $environment = new ChangelogEnvironment($configuration);
        $environment->registerReference(new GenericReference('issue'));

        $resolved = $environment->resolve('issue', '12345');
        self::assertInstanceOf(ResolvedReference::class, $resolved);
        self::assertSame('https://forge.typo3.org/issues/12345', $resolved->getUrl());
    }
}
