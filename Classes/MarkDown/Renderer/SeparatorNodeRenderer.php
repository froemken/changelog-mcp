<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Renderers\NodeRenderer;
use Doctrine\RST\Templates\TemplateRenderer;

final readonly class SeparatorNodeRenderer implements NodeRenderer
{
    public function __construct(
        private TemplateRenderer $templateRenderer,
    ) {}

    public function render(): string
    {
        return $this->templateRenderer->render('separator.md.twig');
    }
}
