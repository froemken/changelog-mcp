<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown\Renderer;

use Doctrine\RST\Environment;
use Doctrine\RST\Nodes\SpanNode;
use Doctrine\RST\References\ResolvedReference;
use Doctrine\RST\Templates\TemplateRenderer;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\SpanNodeRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SpanNodeRendererTest extends UnitTestCase
{
    #[Test]
    public function inlineFormattingMethodsDelegateToTemplateRenderer(): void
    {
        $environmentMock = self::createStub(Environment::class);
        $spanNodeMock = self::createStub(SpanNode::class);
        $templateRendererMock = $this->createMock(TemplateRenderer::class);

        $templateRendererMock->expects($this->exactly(5))
            ->method('render')
            ->willReturnMap([
                ['emphasis.md.twig', ['text' => 'italic text'], '*italic text*'],
                ['strong-emphasis.md.twig', ['text' => 'bold text'], '**bold text**'],
                ['nbsp.md.twig', [], ' '],
                ['br.md.twig', [], "\n"],
                ['literal.md.twig', ['text' => 'code()'], '`code()`'],
            ]);

        $renderer = new SpanNodeRenderer($environmentMock, $spanNodeMock, $templateRendererMock);

        self::assertSame('*italic text*', $renderer->emphasis('italic text'));
        self::assertSame('**bold text**', $renderer->strongEmphasis('bold text'));
        self::assertSame(' ', $renderer->nbsp());
        self::assertSame("\n", $renderer->br());
        self::assertSame('`code()`', $renderer->literal('code()'));
        self::assertSame('unescaped text', $renderer->escape('unescaped text'));
    }

    #[Test]
    public function linkDelegatesToTemplateRenderer(): void
    {
        $environmentMock = self::createStub(Environment::class);
        $spanNodeMock = self::createStub(SpanNode::class);
        $templateRendererMock = $this->createMock(TemplateRenderer::class);

        $templateRendererMock->expects($this->once())
            ->method('render')
            ->with('link.md.twig', [
                'type' => 'href',
                'url' => 'https://example.org',
                'title' => 'Example',
                'attributes' => ['class' => 'external'],
            ])
            ->willReturn('[Example](https://example.org)');

        $renderer = new SpanNodeRenderer($environmentMock, $spanNodeMock, $templateRendererMock);
        self::assertSame('[Example](https://example.org)', $renderer->link('https://example.org', 'Example', ['class' => 'external']));
    }

    #[Test]
    public function referenceWithUrlRendersLink(): void
    {
        $environmentMock = self::createStub(Environment::class);
        $spanNodeMock = self::createStub(SpanNode::class);
        $templateRendererMock = $this->createMock(TemplateRenderer::class);

        $templateRendererMock->expects($this->once())
            ->method('render')
            ->with('link.md.twig', [
                'type' => 'href',
                'url' => 'https://forge.typo3.org/issues/12345#note-1',
                'title' => 'Issue #12345',
                'attributes' => [],
            ])
            ->willReturn('[Issue #12345](https://forge.typo3.org/issues/12345#note-1)');

        $renderer = new SpanNodeRenderer($environmentMock, $spanNodeMock, $templateRendererMock);

        $reference = new ResolvedReference(null, '12345', 'https://forge.typo3.org/issues/12345', [], ['role' => 'issue']);
        $value = ['text' => 'Issue #12345', 'anchor' => '#note-1'];

        self::assertSame('[Issue #12345](https://forge.typo3.org/issues/12345#note-1)', $renderer->reference($reference, $value));
    }

    #[Test]
    public function referenceWithoutUrlAndDocRoleRendersLiteralWithBasename(): void
    {
        $environmentMock = self::createStub(Environment::class);
        $spanNodeMock = self::createStub(SpanNode::class);
        $templateRendererMock = $this->createMock(TemplateRenderer::class);

        $templateRendererMock->expects($this->once())
            ->method('render')
            ->with('literal.md.twig', ['text' => 'See Guide (Index)'])
            ->willReturn('`See Guide (Index)`');

        $renderer = new SpanNodeRenderer($environmentMock, $spanNodeMock, $templateRendererMock);

        $reference = new ResolvedReference(null, 'Changelog/14.0/Index', null, [], ['role' => 'doc']);
        $value = ['text' => 'See Guide'];

        self::assertSame('`See Guide (Index)`', $renderer->reference($reference, $value));
    }

    #[Test]
    public function referenceWithoutUrlAndRefRoleWithoutCustomTextRendersTargetAsLiteral(): void
    {
        $environmentMock = self::createStub(Environment::class);
        $spanNodeMock = self::createStub(SpanNode::class);
        $templateRendererMock = $this->createMock(TemplateRenderer::class);

        $templateRendererMock->expects($this->once())
            ->method('render')
            ->with('literal.md.twig', ['text' => 't3coreapi:some-anchor'])
            ->willReturn('`t3coreapi:some-anchor`');

        $renderer = new SpanNodeRenderer($environmentMock, $spanNodeMock, $templateRendererMock);

        $reference = new ResolvedReference(null, 't3coreapi:some-anchor', null, [], ['role' => 'ref']);
        $value = [];

        self::assertSame('`t3coreapi:some-anchor`', $renderer->reference($reference, $value));
    }
}
