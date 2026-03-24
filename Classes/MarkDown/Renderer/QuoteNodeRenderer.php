<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Nodes\QuoteNode;
use Doctrine\RST\Renderers\NodeRenderer;
use Doctrine\RST\Templates\TemplateRenderer;

final readonly class QuoteNodeRenderer implements NodeRenderer
{
    public function __construct(
        private QuoteNode $quoteNode,
        private TemplateRenderer $templateRenderer,
    ) {}

    public function render(): string
    {
        $lines = explode("\n", $this->quoteNode->getValue()->render());
        foreach ($lines as &$line) {
            $line = '> ' . $line;
        }

        return $this->templateRenderer->render('quote.md.twig', [
            'quote' => implode("\n", $lines),
        ]);
    }
}
