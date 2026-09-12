<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\Service\Changelog;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ChangelogTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnConstructedValues(): void
    {
        $rst = '.. include:: ../some.rst';
        $md = "# Feature: #12345 - Test Feature\n\nSome text";
        $path = '/var/www/typo3/14.2/feature-12345-test-feature.rst';

        $changelog = new Changelog($rst, $md, $path);

        self::assertSame($rst, $changelog->getRstContent());
        self::assertSame($md, $changelog->getMdContent());
        self::assertSame($path, $changelog->getAbsFile());
        self::assertSame('feature-12345-test-feature.rst', $changelog->getFilename());
        self::assertSame('feature-12345-test-feature.md', $changelog->getMarkDownFilename());
        self::assertSame('14.2', $changelog->getVersionString());
        self::assertSame('feature', $changelog->getChangeType());
        self::assertSame(12345, $changelog->getIssueNumber());
        self::assertSame(14, $changelog->getMajorVersion());
        self::assertNull($changelog->getTags());
    }

    #[Test]
    public function getVersionStringStripsDotX(): void
    {
        $changelog = new Changelog('', '', '/var/www/typo3/13.x/breaking-99-remove-something.rst');
        self::assertSame('13', $changelog->getVersionString());
        self::assertSame(13, $changelog->getMajorVersion());
    }

    public static function titleExtractionDataProvider(): array
    {
        return [
            'Breaking change with prefix and issue number' => [
                "# Breaking: #59659 - Removal of deprecated code\n\nSome body text",
                'Removal of deprecated code',
            ],
            'Feature with prefix and issue number' => [
                "# Feature: #12345 - Add new Cool API\n\nSome body text",
                'Add new Cool API',
            ],
            'Arbitrary prefix before issue number' => [
                "# Currywurst: #99999 - Some random text\n\nBody",
                'Some random text',
            ],
            'Multiple markdown hashes' => [
                "### Deprecation: #45678 - Old Method Deprecated\n\nBody",
                'Old Method Deprecated',
            ],
            'Title without issue number' => [
                "# Just A Clean Title\n\nBody",
                'Just A Clean Title',
            ],
            'First header after blank lines' => [
                "\n\n   \n# Important: #11111 - Security Notice\n\nBody",
                'Security Notice',
            ],
            'No header at all' => [
                'Just some body text without any hash header.',
                '',
            ],
            'Empty markdown content' => [
                '',
                '',
            ],
        ];
    }

    #[Test]
    #[DataProvider('titleExtractionDataProvider')]
    public function getTitleExtractsExpectedTitle(string $mdContent, string $expectedTitle): void
    {
        $changelog = new Changelog('', $mdContent, '/path/14.0/feature-1-test.rst');
        self::assertSame($expectedTitle, $changelog->getTitle());
    }

    #[Test]
    public function getDescriptionExtractsOnlyDescriptionSection(): void
    {
        $md = <<<MD
# Feature: #12345 - Test

## Description
This is the description paragraph.
It spans across multiple lines.

## Impact
This impact section should not be included.
MD;

        $changelog = new Changelog('', $md, '/path/14.0/feature-12345-test.rst');
        $expected = "This is the description paragraph.\nIt spans across multiple lines.";

        self::assertSame($expected, $changelog->getDescription());
    }

    #[Test]
    public function getDescriptionTruncatesTo1000CharactersWithEllipsis(): void
    {
        $longText = str_repeat('A', 1200);
        $md = "# Title\n\n## Description\n" . $longText . "\n\n## Impact\nStop here";

        $changelog = new Changelog('', $md, '/path/14.0/feature-12345-test.rst');
        $description = $changelog->getDescription();

        self::assertSame(1000, mb_strlen($description));
        self::assertStringEndsWith('...', $description);
        self::assertSame(str_repeat('A', 997) . '...', $description);
    }

    #[Test]
    public function getDescriptionReturnsEmptyStringWhenNoDescriptionSectionFound(): void
    {
        $md = "# Title\n\n## Summary\nNo description section here.\n\n## Impact\nNone.";

        $changelog = new Changelog('', $md, '/path/14.0/feature-12345-test.rst');
        self::assertSame('', $changelog->getDescription());
    }
}
