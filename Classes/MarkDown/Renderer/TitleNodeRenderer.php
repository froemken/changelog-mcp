<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Nodes\TitleNode;
use Doctrine\RST\Renderers\NodeRenderer;
use Doctrine\RST\Templates\TemplateRenderer;

final readonly class TitleNodeRenderer implements NodeRenderer
{
    public function __construct(
        private TitleNode $titleNode,
        private TemplateRenderer $templateRenderer,
    ) {}

    public function render(): string
    {
        $levelIndent = match($this->titleNode->getLevel()) {
            1 => '#',
            2 => '##',
            3 => '###',
            4 => '####',
            5 => '#####',
            6 => '######',
        };

        if (preg_match('/(Important|Breaking|Deprecated|Feature): #\d+/', $this->titleNode->getValueString())) {
            $levelIndent = '#';
        }

        return $this->templateRenderer->render('title.md.twig', [
            'levelIndent' => $levelIndent,
            'titleNode' => $this->titleNode,
        ]);
    }
}
