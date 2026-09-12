<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown;

use Doctrine\RST\Directives\Directive;
use Doctrine\RST\Formats\Format;
use Doctrine\RST\Nodes\AnchorNode;
use Doctrine\RST\Nodes\CodeNode;
use Doctrine\RST\Nodes\DocumentNode;
use Doctrine\RST\Nodes\ImageNode;
use Doctrine\RST\Nodes\ListNode;
use Doctrine\RST\Nodes\ParagraphNode;
use Doctrine\RST\Nodes\QuoteNode;
use Doctrine\RST\Nodes\SeparatorNode;
use Doctrine\RST\Nodes\SpanNode;
use Doctrine\RST\Nodes\TableNode;
use Doctrine\RST\Nodes\TitleNode;
use Doctrine\RST\Renderers\CallableNodeRendererFactory;
use Doctrine\RST\Renderers\NodeRendererFactory;
use Doctrine\RST\Templates\TemplateRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Attention;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Confval;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Container;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Contents;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\CsvTable;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Hint;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Important;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Index;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Note;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\RstClass;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\SeeAlso;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Sidebar;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Tip;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Title;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Toctree;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\VersionAdded;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Warning;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\AnchorNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\CodeNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\DocumentNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\ImageNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\ListNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\ParagraphNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\QuoteNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\SeparatorNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\SpanNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\TableNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\TitleNodeRenderer;

class MarkDownFormat implements Format
{
    private const FORMAT = 'md';

    public function __construct(private readonly TemplateRenderer $templateRenderer) {}

    public function getFileExtension(): string
    {
        return self::FORMAT;
    }

    /**
     * @return Directive[]
     */
    public function getDirectives(): array
    {
        return [
            new Attention(),
            new Confval(),
            new Container(),
            new Contents(),
            new CsvTable(),
            new Hint(),
            new Important(),
            new Index(),
            new Note(),
            new RstClass(),
            new SeeAlso(),
            new Sidebar(),
            new Tip(),
            new Title(),
            new Toctree(),
            new VersionAdded(),
            new Warning(),
        ];
    }
    /**
     * @return NodeRendererFactory[]
     */
    public function getNodeRendererFactories(): array
    {
        return [
            AnchorNode::class => new CallableNodeRendererFactory(
                fn(AnchorNode $node): AnchorNodeRenderer => new AnchorNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
            CodeNode::class => new CallableNodeRendererFactory(
                fn(CodeNode $node): CodeNodeRenderer => new CodeNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
            DocumentNode::class => new CallableNodeRendererFactory(
                fn(DocumentNode $node): DocumentNodeRenderer => new DocumentNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
            ImageNode::class => new CallableNodeRendererFactory(
                fn(ImageNode $node): ImageNodeRenderer => new ImageNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
            ListNode::class => new CallableNodeRendererFactory(
                fn(ListNode $node): ListNodeRenderer => new ListNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
            ParagraphNode::class => new CallableNodeRendererFactory(
                fn(ParagraphNode $node): ParagraphNodeRenderer => new ParagraphNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
            QuoteNode::class => new CallableNodeRendererFactory(
                fn(QuoteNode $node): QuoteNodeRenderer => new QuoteNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
            SeparatorNode::class => new CallableNodeRendererFactory(
                fn(SeparatorNode $node): SeparatorNodeRenderer => new SeparatorNodeRenderer(
                    $this->templateRenderer,
                ),
            ),
            SpanNode::class => new CallableNodeRendererFactory(
                fn(SpanNode $node): SpanNodeRenderer => new SpanNodeRenderer(
                    $node->getEnvironment(),
                    $node,
                    $this->templateRenderer,
                ),
            ),
            TableNode::class => new CallableNodeRendererFactory(
                fn(TableNode $node): TableNodeRenderer => new TableNodeRenderer(
                    $node,
                ),
            ),
            TitleNode::class => new CallableNodeRendererFactory(
                fn(TitleNode $node): TitleNodeRenderer => new TitleNodeRenderer(
                    $node,
                    $this->templateRenderer,
                ),
            ),
        ];
    }
}
