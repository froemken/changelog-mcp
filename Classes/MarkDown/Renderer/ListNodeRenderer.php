<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Nodes\ListNode;
use Doctrine\RST\Renderers\NodeRenderer;
use Doctrine\RST\Templates\TemplateRenderer;

final readonly class ListNodeRenderer implements NodeRenderer
{
    public function __construct(
        private ListNode $listNode,
        private TemplateRenderer $templateRenderer,
    ) {}

    public function render(): string
    {
        $template = 'bullet-list.md.twig';
        if ($this->listNode->isOrdered()) {
            $template = 'enumerated-list.md.twig';
        }

        return $this->templateRenderer->render($template, [
            'listNode' => $this->listNode,
        ]);
    }
}
