<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Service;

use Doctrine\RST\Nodes\DocumentNode;
use Doctrine\RST\Parser;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\ParserFactory;
use StefanFroemken\ChangelogMcp\Service\Changelog;
use StefanFroemken\ChangelogMcp\Service\ChangelogService;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ChangelogServiceTest extends UnitTestCase
{
    #[Test]
    public function getChangelogReturnsNullForNonExistentOrUnreadableFile(): void
    {
        $parserFactoryMock = $this->createMock(ParserFactory::class);
        $parserFactoryMock->expects($this->never())->method('getParser');

        $service = new ChangelogService($parserFactoryMock);

        self::assertNull($service->getChangelog('/non/existent/file.rst'));
    }

    #[Test]
    public function getChangelogParsesFileAndCleansUpContent(): void
    {
        $tempFile = StringUtility::getUniqueId('test_changelog_') . '.rst';
        $tempPath = sys_get_temp_dir() . '/' . $tempFile;

        $rstSource = <<<RST
.. include:: ../some-include.rst

..   Important::
   Notice something

..    note::
   Here is a note

==========================================
Breaking: #12345 - Removed legacy feature
==========================================

Some description.
RST;

        file_put_contents($tempPath, $rstSource);

        $documentMock = self::createStub(DocumentNode::class);
        $documentMock->method('render')->willReturn("# Breaking: #12345 - Removed legacy feature\n\nSome description.");

        $parserMock = $this->createMock(Parser::class);
        $parserMock->expects($this->once())
            ->method('parse')
            ->with(self::callback(function (string $cleanedContent): bool {
                self::assertStringNotContainsString('.. include::', $cleanedContent);
                self::assertStringContainsString('.. important::', $cleanedContent);
                self::assertStringContainsString('.. note::', $cleanedContent);
                return true;
            }))
            ->willReturn($documentMock);

        $parserFactoryMock = $this->createMock(ParserFactory::class);
        $parserFactoryMock->expects($this->once())
            ->method('getParser')
            ->willReturn($parserMock);

        $service = new ChangelogService($parserFactoryMock);
        $changelog = $service->getChangelog($tempPath);

        unlink($tempPath);

        self::assertInstanceOf(Changelog::class, $changelog);
        self::assertSame($tempPath, $changelog->getAbsFile());
        self::assertSame("# Breaking: #12345 - Removed legacy feature\n\nSome description.", $changelog->getMdContent());
        self::assertStringNotContainsString('.. include::', $changelog->getRstContent());
    }

    #[Test]
    public function getAllOriginalTypo3ChangelogFilesReturnsArray(): void
    {
        $parserFactoryMock = self::createStub(ParserFactory::class);
        $service = new ChangelogService($parserFactoryMock);

        $files = $service->getAllOriginalTypo3ChangelogFiles();
        self::assertGreaterThanOrEqual(0, count($files));
        foreach ($files as $file) {
            self::assertIsString($file);
        }
    }
}
