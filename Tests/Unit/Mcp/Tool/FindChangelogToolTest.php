<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Mcp\Tool;

use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextContent;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use StefanFroemken\ChangelogMcp\Mcp\Tool\FindChangelogTool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FindChangelogToolTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function searchFormatsResultsAndCreatesEmbeddedResources(): void
    {
        $repositoryMock = $this->createMock(ChangelogRepository::class);
        $repositoryMock->expects($this->once())
            ->method('getChangelogs')
            ->with('request', '14.0', 'breaking')
            ->willReturn([
                [
                    'uid' => 101,
                    'title' => 'ServerRequest changes',
                    'change_type' => 'Breaking',
                    'version_string' => '14.0',
                    'summary' => 'Details about request changes',
                ],
            ]);

        $tool = new FindChangelogTool($repositoryMock);
        $result = $tool->search('request', '14.0', 'breaking');

        $content = $result->content;
        self::assertCount(2, $content);
        self::assertInstanceOf(TextContent::class, $content[0]);
        self::assertStringContainsString('I found 1 matching changelogs:', $content[0]->text);
        self::assertStringContainsString('typo3://changelog/101', $content[0]->text);
        self::assertStringContainsString('ServerRequest changes', $content[0]->text);
        self::assertStringContainsString('Summary: Details about request changes', $content[0]->text);

        self::assertInstanceOf(EmbeddedResource::class, $content[1]);
        self::assertSame('typo3://changelog/101', $content[1]->resource->uri);
        self::assertSame('ServerRequest changes', $content[1]->resource->text);
        self::assertSame('text/markdown', $content[1]->resource->mimeType);
    }

    #[Test]
    public function searchNormalizesEmptyStringToNull(): void
    {
        $repositoryMock = $this->createMock(ChangelogRepository::class);
        $repositoryMock->expects($this->once())
            ->method('getChangelogs')
            ->with(null, null, null)
            ->willReturn([]);

        $tool = new FindChangelogTool($repositoryMock);
        $result = $tool->search('', '', null);

        self::assertCount(1, $result->content);
        self::assertStringContainsString('I found 0 matching changelogs:', $result->content[0]->text);
    }

    #[Test]
    public function showChangelogReturnsContentWhenFound(): void
    {
        $repositoryMock = $this->createMock(ChangelogRepository::class);
        $repositoryMock->expects($this->once())
            ->method('getChangelogContentByUid')
            ->with(101)
            ->willReturn('# Breaking: #101 - Some title');

        $tool = new FindChangelogTool($repositoryMock);
        $result = $tool->showChangelog(101);

        self::assertFalse($result->isError);
        self::assertCount(1, $result->content);
        self::assertInstanceOf(TextContent::class, $result->content[0]);
        self::assertSame('# Breaking: #101 - Some title', $result->content[0]->text);
    }

    #[Test]
    public function showChangelogReturnsErrorWhenNotFound(): void
    {
        $repositoryMock = $this->createMock(ChangelogRepository::class);
        $repositoryMock->expects($this->once())
            ->method('getChangelogContentByUid')
            ->with(999)
            ->willReturn(null);

        $tool = new FindChangelogTool($repositoryMock);
        $result = $tool->showChangelog(999);

        self::assertTrue($result->isError);
        self::assertCount(1, $result->content);
        self::assertInstanceOf(TextContent::class, $result->content[0]);
        self::assertSame('Changelog with ID 999 not found.', $result->content[0]->text);
    }
}
