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
use Doctrine\RST\Renderers\CallableNodeRendererFactory;
use Doctrine\RST\Renderers\NodeRendererFactory;
use Doctrine\RST\Templates\TemplateRenderer;
use Doctrine\RST\Nodes;
use StefanFroemken\ChangelogMcp\MarkDown;

class MarkDownFormat implements Format
{
    private const FORMAT = 'md';

    private TemplateRenderer $templateRenderer;

    public function __construct(TemplateRenderer $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    public function getFileExtension() : string
    {
        return self::FORMAT;
    }

    /**
     * @return Directive[]
     */
    public function getDirectives() : array
    {
        return [
            new MarkDown\Directive\Attention(),
            new MarkDown\Directive\Container(),
            new MarkDown\Directive\Contents(),
            new MarkDown\Directive\Hint(),
            new MarkDown\Directive\Important(),
            new MarkDown\Directive\Index(),
            new MarkDown\Directive\Note(),
            new MarkDown\Directive\RstClass(),
            new MarkDown\Directive\SeeAlso(),
            new MarkDown\Directive\Sidebar(),
            new MarkDown\Directive\Title(),
            new MarkDown\Directive\VersionAdded(),
            new MarkDown\Directive\Warning(),
        ];
    }
    /**
     * @return NodeRendererFactory[]
     */
    public function getNodeRendererFactories() : array
    {
        return [
            Nodes\AnchorNode::class => new CallableNodeRendererFactory(
                function (Nodes\AnchorNode $node): MarkDown\Renderer\AnchorNodeRenderer {
                    return new MarkDown\Renderer\AnchorNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\CodeNode::class => new CallableNodeRendererFactory(
                function (Nodes\CodeNode $node): MarkDown\Renderer\CodeNodeRenderer {
                    return new MarkDown\Renderer\CodeNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\DocumentNode::class => new CallableNodeRendererFactory(
                function (Nodes\DocumentNode $node): MarkDown\Renderer\DocumentNodeRenderer {
                    return new MarkDown\Renderer\DocumentNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\ImageNode::class => new CallableNodeRendererFactory(
                function (Nodes\ImageNode $node): MarkDown\Renderer\ImageNodeRenderer {
                    return new MarkDown\Renderer\ImageNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\ListNode::class => new CallableNodeRendererFactory(
                function (Nodes\ListNode $node): MarkDown\Renderer\ListNodeRenderer {
                    return new MarkDown\Renderer\ListNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\ParagraphNode::class => new CallableNodeRendererFactory(
                function (Nodes\ParagraphNode $node): MarkDown\Renderer\ParagraphNodeRenderer {
                    return new MarkDown\Renderer\ParagraphNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\QuoteNode::class => new CallableNodeRendererFactory(
                function (Nodes\QuoteNode $node): MarkDown\Renderer\QuoteNodeRenderer {
                    return new MarkDown\Renderer\QuoteNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\SeparatorNode::class => new CallableNodeRendererFactory(
                function (Nodes\SeparatorNode $node): MarkDown\Renderer\SeparatorNodeRenderer {
                    return new MarkDown\Renderer\SeparatorNodeRenderer(
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\SpanNode::class => new CallableNodeRendererFactory(
                function (Nodes\SpanNode $node): MarkDown\Renderer\SpanNodeRenderer {
                    return new MarkDown\Renderer\SpanNodeRenderer(
                        $node->getEnvironment(),
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
            Nodes\TableNode::class => new CallableNodeRendererFactory(
                function (Nodes\TableNode $node): MarkDown\Renderer\TableNodeRenderer {
                    return new MarkDown\Renderer\TableNodeRenderer(
                        $node,
                    );
                }
            ),
            Nodes\TitleNode::class => new CallableNodeRendererFactory(
                function (Nodes\TitleNode $node): MarkDown\Renderer\TitleNodeRenderer {
                    return new MarkDown\Renderer\TitleNodeRenderer(
                        $node,
                        $this->templateRenderer,
                    );
                }
            ),
        ];
    }
}
