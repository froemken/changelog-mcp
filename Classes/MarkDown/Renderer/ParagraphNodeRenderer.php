<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Nodes\ParagraphNode;
use Doctrine\RST\Renderers\NodeRenderer;
use Doctrine\RST\Templates\TemplateRenderer;

final readonly class ParagraphNodeRenderer implements NodeRenderer
{
    public function __construct(
        private ParagraphNode $paragraphNode,
        private TemplateRenderer $templateRenderer,
    ) {}

    public function render(): string
    {
        return $this->templateRenderer->render('paragraph.md.twig', [
            'paragraphNode' => $this->paragraphNode,
        ]);
    }
}
