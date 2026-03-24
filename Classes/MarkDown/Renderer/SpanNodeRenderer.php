<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Environment;
use Doctrine\RST\Nodes\SpanNode;
use Doctrine\RST\References\ResolvedReference;
use Doctrine\RST\Renderers\SpanNodeRenderer as BaseSpanNodeRenderer;
use Doctrine\RST\Templates\TemplateRenderer;

use function is_string;
use function substr;
use function trim;

final class SpanNodeRenderer extends BaseSpanNodeRenderer
{
    public function __construct(
        Environment $environment,
        SpanNode $span,
        private readonly TemplateRenderer $templateRenderer
    ) {
        parent::__construct($environment, $span);
    }

    public function emphasis(string $text): string
    {
        return $this->templateRenderer->render('emphasis.md.twig', ['text' => $text]);
    }

    public function strongEmphasis(string $text): string
    {
        return $this->templateRenderer->render('strong-emphasis.md.twig', ['text' => $text]);
    }

    public function nbsp(): string
    {
        return $this->templateRenderer->render('nbsp.md.twig');
    }

    public function br(): string
    {
        return $this->templateRenderer->render('br.md.twig');
    }

    public function literal(string $text): string
    {
        return $this->templateRenderer->render('literal.md.twig', ['text' => $text]);
    }

    /**
     * @param mixed[] $attributes
     */
    public function link(?string $url, string $title, array $attributes = []): string
    {
        $type = 'href';

        return $this->templateRenderer->render('link.md.twig', [
            'type' => $type,
            'url' => $url,
            'title' => $title,
            'attributes' => $attributes,
        ]);
    }

    public function escape(string $span): string
    {
        return $span;
    }

    /** @param mixed[] $value */
    public function reference(ResolvedReference $reference, array $value): string
    {
        $text = (bool) $value['text'] ? $value['text'] : $reference->getTitle();
        $url  = $reference->getUrl();

        if ($value['anchor'] !== '') {
            $url .= $value['anchor'];
        }

        if ($text === null) {
            $text = '';
        }

        if ($url === null) {
            $url = '';
        }

        return $this->link($url, trim($text));
    }
}
