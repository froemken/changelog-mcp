<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown\Reference;

use Doctrine\RST\Environment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\Reference\GenericReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GenericReferenceTest extends UnitTestCase
{
    #[Test]
    public function getNameReturnsConfiguredName(): void
    {
        $ref = new GenericReference('issue');
        self::assertSame('issue', $ref->getName());

        $refDoc = new GenericReference('doc');
        self::assertSame('doc', $refDoc->getName());
    }

    #[Test]
    public function resolveIssueConstructsForgeUrl(): void
    {
        $environmentMock = $this->createMock(Environment::class);
        $ref = new GenericReference('issue');

        $resolved = $ref->resolve($environmentMock, '12345');

        self::assertNotNull($resolved);
        self::assertSame('12345', $resolved->getTitle());
        self::assertSame('https://forge.typo3.org/issues/12345', $resolved->getUrl());
        self::assertSame(['role' => 'issue'], $resolved->getAttributes());
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function nonIssueRolesDataProvider(): array
    {
        return [
            ['ref', 't3coreapi:some-anchor'],
            ['doc', 'Changelog/14.0/Index'],
            ['class', GeneralUtility::class],
            ['php', 'explode()'],
        ];
    }

    #[Test]
    #[DataProvider('nonIssueRolesDataProvider')]
    public function resolveNonIssueRolesReturnsNullUrlAndSetsRoleAttribute(string $role, string $data): void
    {
        $environmentMock = $this->createMock(Environment::class);
        $ref = new GenericReference($role);

        $resolved = $ref->resolve($environmentMock, $data);

        self::assertNotNull($resolved);
        self::assertSame($data, $resolved->getTitle());
        self::assertNull($resolved->getUrl());
        self::assertSame(['role' => $role], $resolved->getAttributes());
    }
}
