<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Nodes\CodeNode;
use Doctrine\RST\Renderers\NodeRenderer;
use Doctrine\RST\Templates\TemplateRenderer;

final readonly class CodeNodeRenderer implements NodeRenderer
{
    public function __construct(
        private CodeNode $codeNode,
        private TemplateRenderer $templateRenderer,
    ) {}

    public function render(): string
    {
        if ($this->codeNode->isRaw()) {
            return $this->codeNode->getValue();
        }

        return $this->templateRenderer->render('code.md.twig', [
            'codeNode' => $this->codeNode,
        ]);
    }
}
