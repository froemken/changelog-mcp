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
use Doctrine\RST\Nodes\WrapperNode;
use Doctrine\RST\Parser;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Confval;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Container;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Contents;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\CsvTable;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Index;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\RstClass;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Sidebar;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Title;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Toctree;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\VersionAdded;
use StefanFroemken\ChangelogMcp\MarkDown\Node\AdmonitionNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OtherDirectivesTest extends UnitTestCase
{
    #[Test]
    public function confvalWrapsDocumentWithHeader(): void
    {
        $directive = new Confval();
        self::assertSame('confval', $directive->getName());

        $parserMock = self::createStub(Parser::class);
        $documentMock = self::createStub(Node::class);
        $documentMock->method('render')->willReturn('Option description');

        $node = $directive->processSub($parserMock, $documentMock, '', 'my.config.key', []);
        self::assertInstanceOf(WrapperNode::class, $node);
        self::assertSame("### my.config.key\n\nOption description\n", $node->render());

        self::assertNull($directive->processSub($parserMock, null, '', 'key', []));
    }

    #[Test]
    public function containerPassesDocumentThrough(): void
    {
        $directive = new Container();
        self::assertSame('container', $directive->getName());

        $parserMock = self::createStub(Parser::class);
        $documentMock = self::createStub(Node::class);

        $node = $directive->processSub($parserMock, $documentMock, '', '', []);
        self::assertSame($documentMock, $node);
    }

    #[Test]
    public function noopDirectivesExecuteWithoutError(): void
    {
        $parserMock = self::createStub(Parser::class);

        $contents = new Contents();
        self::assertSame('contents', $contents->getName());
        $contents->process($parserMock, null, '', '', []);

        $index = new Index();
        self::assertSame('index', $index->getName());
        $index->process($parserMock, null, '', '', []);

        $rstClass = new RstClass();
        self::assertSame('rst-class', $rstClass->getName());
        $rstClass->process($parserMock, null, '', '', []);

        $toctree = new Toctree();
        self::assertSame('toctree', $toctree->getName());
        $toctree->process($parserMock, null, '', '', []);
    }

    #[Test]
    public function sidebarCreatesAdmonitionWithHeader(): void
    {
        $directive = new Sidebar();
        self::assertSame('sidebar', $directive->getName());

        $parserMock = self::createStub(Parser::class);
        $documentMock = self::createStub(Node::class);
        $documentMock->method('render')->willReturn('Sidebar body text');

        $node = $directive->processSub($parserMock, $documentMock, '', 'My Sidebar', []);
        self::assertInstanceOf(AdmonitionNode::class, $node);

        $rendered = $node->render();
        self::assertStringStartsWith("> ### My Sidebar\n> Sidebar body text", $rendered);

        self::assertNull($directive->processSub($parserMock, null, '', 'My Sidebar', []));
    }

    #[Test]
    public function titleAddsHeaderNodeToDocument(): void
    {
        $directive = new Title();
        self::assertSame('title', $directive->getName());

        $documentMock = $this->createMock(DocumentNode::class);
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $parserMock = self::createStub(Parser::class);

        $parserMock->method('getDocument')->willReturn($documentMock);
        $parserMock->method('getNodeFactory')->willReturn($nodeFactoryMock);

        $rawNode = new RawNode('\title{Document Title}');
        $nodeFactoryMock->expects($this->once())
            ->method('createRawNode')
            ->with('\title{Document Title}')
            ->willReturn($rawNode);

        $documentMock->expects($this->once())
            ->method('addHeaderNode')
            ->with($rawNode);

        $nodeMock = self::createStub(Node::class);
        $documentMock->expects($this->once())
            ->method('addNode')
            ->with($nodeMock);

        $directive->process($parserMock, $nodeMock, '', 'Document Title', []);
    }

    #[Test]
    public function versionAddedCreatesAdmonitionWithVersionString(): void
    {
        $directive = new VersionAdded();
        self::assertSame('versionadded', $directive->getName());

        $parserMock = self::createStub(Parser::class);
        $documentMock = self::createStub(Node::class);
        $documentMock->method('render')->willReturn('This method was introduced.');

        $node = $directive->processSub($parserMock, $documentMock, '', '14.0', []);
        self::assertInstanceOf(AdmonitionNode::class, $node);

        $rendered = $node->render();
        self::assertStringStartsWith("> **Added in version 14.0:**\n> This method was introduced.", $rendered);

        self::assertNull($directive->processSub($parserMock, null, '', '14.0', []));
    }

    #[Test]
    public function csvTableGeneratesMarkdownTable(): void
    {
        $directive = new CsvTable();
        self::assertSame('csv-table', $directive->getName());

        $parserMock = self::createStub(Parser::class);
        $documentMock = self::createStub(DocumentNode::class);
        $nodeFactoryMock = $this->createMock(NodeFactory::class);

        $parserMock->method('getDocument')->willReturn($documentMock);
        $parserMock->method('getNodeFactory')->willReturn($nodeFactoryMock);

        $inputNodeMock = self::createStub(Node::class);
        $inputNodeMock->method('getValue')->willReturn("\"Val 1\", \"Val 2\"\n\"Val 3\", \"Val 4\"");

        $nodeFactoryMock->expects($this->once())
            ->method('createRawNode')
            ->with(self::callback(function (string $markdown): bool {
                self::assertStringContainsString('**Table Caption**', $markdown);
                self::assertStringContainsString('| Head 1 | Head 2 |', $markdown);
                self::assertStringContainsString('| --- | --- |', $markdown);
                self::assertStringContainsString('| Val 1 | Val 2 |', $markdown);
                self::assertStringContainsString('| Val 3 | Val 4 |', $markdown);
                return true;
            }))
            ->willReturn(new RawNode(''));

        $directive->process($parserMock, $inputNodeMock, '', 'Table Caption', [
            'header' => 'Head 1, Head 2',
        ]);
    }

    #[Test]
    public function csvTableReturnsEarlyWhenNodeIsNull(): void
    {
        $directive = new CsvTable();
        $parserMock = $this->createMock(Parser::class);
        $parserMock->expects($this->never())->method('getDocument');

        $directive->process($parserMock, null, '', '', []);
    }
}
