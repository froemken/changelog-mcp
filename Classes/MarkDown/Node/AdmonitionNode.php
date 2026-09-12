<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Node;

use Doctrine\RST\Nodes\Node;

final class AdmonitionNode extends Node
{
    public function __construct(
        private readonly Node $document,
        private readonly string $header,
    ) {
        parent::__construct();
    }

    protected function doRender(): string
    {
        $content = $this->document->render();
        $lines = explode("\n", trim($content));
        $quotedLines = array_map(
            static fn(string $line): string => $line === '' ? '>' : '> ' . $line,
            $lines,
        );

        return $this->header . implode("\n", $quotedLines) . "\n\n";
    }
}
