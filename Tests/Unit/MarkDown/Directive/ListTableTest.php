<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown\Directive;

use Doctrine\RST\NodeFactory\NodeFactory;
use Doctrine\RST\Nodes\DocumentNode;
use Doctrine\RST\Nodes\Node;
use Doctrine\RST\Nodes\RawNode;
use Doctrine\RST\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\ListTable;
use StefanFroemken\ChangelogMcp\MarkDown\ParserFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ListTableTest extends UnitTestCase
{
    #[Test]
    public function getNameReturnsListTable(): void
    {
        $directive = new ListTable();
        self::assertSame('list-table', $directive->getName());
    }

    #[Test]
    public function processReturnsEarlyWhenNodeIsNull(): void
    {
        $directive = new ListTable();
        $parserMock = $this->createMock(Parser::class);
        $parserMock->expects($this->never())->method('getDocument');

        $directive->process($parserMock, null, '', '', []);
    }

    #[Test]
    public function processReturnsEarlyWhenContentIsEmpty(): void
    {
        $directive = new ListTable();
        $parserMock = $this->createMock(Parser::class);
        $parserMock->expects($this->never())->method('getDocument');

        $inputNode = self::createStub(Node::class);
        $inputNode->method('getValue')->willReturn("   \n\n  ");

        $directive->process($parserMock, $inputNode, '', '', []);
    }

    #[Test]
    public function processRendersTableWithCaptionAndDefaultHeader(): void
    {
        $directive = new ListTable();
        $parser = $this->createParserMock();

        $content = <<<'RST'
* - Header 1
  - Header 2
* - Row 1 Col 1
  - Row 1 Col 2
RST;

        $inputNode = self::createStub(Node::class);
        $inputNode->method('getValue')->willReturn($content);

        $capturedMarkdown = '';
        $this->expectRawNodeCreation($parser, function (string $md) use (&$capturedMarkdown): bool {
            $capturedMarkdown = $md;
            return true;
        });

        $directive->process($parser, $inputNode, '', 'My Caption', []);

        self::assertStringStartsWith("**My Caption**\n\n", $capturedMarkdown);
        self::assertStringContainsString('| Header 1 | Header 2 |', $capturedMarkdown);
        self::assertStringContainsString('| --- | --- |', $capturedMarkdown);
        self::assertStringContainsString('| Row 1 Col 1 | Row 1 Col 2 |', $capturedMarkdown);
    }

    #[Test]
    public function processRendersEmptyHeaderWhenHeaderRowsIsZero(): void
    {
        $directive = new ListTable();
        $parser = $this->createParserMock();

        $content = <<<'RST'
* - Row 1 Col 1
  - Row 1 Col 2
* - Row 2 Col 1
  - Row 2 Col 2
RST;

        $inputNode = self::createStub(Node::class);
        $inputNode->method('getValue')->willReturn($content);

        $capturedMarkdown = '';
        $this->expectRawNodeCreation($parser, function (string $md) use (&$capturedMarkdown): bool {
            $capturedMarkdown = $md;
            return true;
        });

        $directive->process($parser, $inputNode, '', '', ['header-rows' => '0']);

        self::assertStringStartsNotWith('**', $capturedMarkdown);
        self::assertStringContainsString("|  |  |\n| --- | --- |\n", $capturedMarkdown);
        self::assertStringContainsString('| Row 1 Col 1 | Row 1 Col 2 |', $capturedMarkdown);
        self::assertStringContainsString('| Row 2 Col 1 | Row 2 Col 2 |', $capturedMarkdown);
    }

    #[Test]
    public function processSupportsSeparateRowBulletSyntax(): void
    {
        $directive = new ListTable();
        $parser = $this->createParserMock();

        $content = <<<'RST'
*
  - H1
  - H2
*
  - V1
  - V2
RST;

        $inputNode = self::createStub(Node::class);
        $inputNode->method('getValue')->willReturn($content);

        $capturedMarkdown = '';
        $this->expectRawNodeCreation($parser, function (string $md) use (&$capturedMarkdown): bool {
            $capturedMarkdown = $md;
            return true;
        });

        $directive->process($parser, $inputNode, '', '', ['header-rows' => '1']);

        self::assertStringContainsString('| H1 | H2 |', $capturedMarkdown);
        self::assertStringContainsString('| --- | --- |', $capturedMarkdown);
        self::assertStringContainsString('| V1 | V2 |', $capturedMarkdown);
    }

    #[Test]
    public function processSupportsMultilineCells(): void
    {
        $directive = new ListTable();
        $parser = $this->createParserMock();

        $content = <<<'RST'
* - Head 1
  - Head 2
* - Line 1 of Col 1
    Line 2 of Col 1
  - Simple Col 2
RST;

        $inputNode = self::createStub(Node::class);
        $inputNode->method('getValue')->willReturn($content);

        $capturedMarkdown = '';
        $this->expectRawNodeCreation($parser, function (string $md) use (&$capturedMarkdown): bool {
            $capturedMarkdown = $md;
            return true;
        });

        $directive->process($parser, $inputNode, '', '', []);

        self::assertStringContainsString('| Line 1 of Col 1 Line 2 of Col 1 | Simple Col 2 |', $capturedMarkdown);
    }

    #[Test]
    public function processEscapesPipeCharactersInCells(): void
    {
        $directive = new ListTable();
        $parser = $this->createParserMock();

        $content = <<<'RST'
* - Type
  - Description
* - string|int
  - Supports string or int
RST;

        $inputNode = self::createStub(Node::class);
        $inputNode->method('getValue')->willReturn($content);

        $capturedMarkdown = '';
        $this->expectRawNodeCreation($parser, function (string $md) use (&$capturedMarkdown): bool {
            $capturedMarkdown = $md;
            return true;
        });

        $directive->process($parser, $inputNode, '', '', []);

        self::assertStringContainsString('| string\|int | Supports string or int |', $capturedMarkdown);
    }

    #[Test]
    public function processPadsUnequalColumns(): void
    {
        $directive = new ListTable();
        $parser = $this->createParserMock();

        $content = <<<'RST'
* - C1
  - C2
* - R1C1
  - R1C2
  - R1C3
RST;

        $inputNode = self::createStub(Node::class);
        $inputNode->method('getValue')->willReturn($content);

        $capturedMarkdown = '';
        $this->expectRawNodeCreation($parser, function (string $md) use (&$capturedMarkdown): bool {
            $capturedMarkdown = $md;
            return true;
        });

        $directive->process($parser, $inputNode, '', '', []);

        self::assertStringContainsString('| C1 | C2 |  |', $capturedMarkdown);
        self::assertStringContainsString('| --- | --- | --- |', $capturedMarkdown);
        self::assertStringContainsString('| R1C1 | R1C2 | R1C3 |', $capturedMarkdown);
    }

    #[Test]
    public function endToEndParsingViaParserFactory(): void
    {
        $parser = (new ParserFactory())->getParser();
        $rst = <<<'RST'
Section Title
=============

.. list-table:: Table Caption
   :header-rows: 1

   * - Event
     - Description
   * - view/ready
     - All modules initialized
RST;

        $document = $parser->parse($rst);
        $markdown = $document->render();

        self::assertStringContainsString('**Table Caption**', $markdown);
        self::assertStringContainsString('| Event | Description |', $markdown);
        self::assertStringContainsString('| --- | --- |', $markdown);
        self::assertStringContainsString('| view/ready | All modules initialized |', $markdown);
    }

    /**
     * @return Parser&MockObject
     */
    private function createParserMock(): Parser&MockObject
    {
        $parserMock = $this->createMock(Parser::class);
        $documentMock = self::createStub(DocumentNode::class);
        $parserMock->method('getDocument')->willReturn($documentMock);

        return $parserMock;
    }

    /**
     * @param Parser&MockObject $parser
     * @param callable(string): bool $callback
     */
    private function expectRawNodeCreation(Parser&MockObject $parser, callable $callback): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $parser->method('getNodeFactory')->willReturn($nodeFactoryMock);

        $nodeFactoryMock->expects($this->once())
            ->method('createRawNode')
            ->with(self::callback($callback))
            ->willReturn(new RawNode(''));
    }
}
