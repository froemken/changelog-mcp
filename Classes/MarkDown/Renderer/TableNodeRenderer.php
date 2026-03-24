<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Renderer;

use Doctrine\RST\Nodes\TableNode;
use Doctrine\RST\Renderers\NodeRenderer;

use function count;
use function implode;
use function max;

final readonly class TableNodeRenderer implements NodeRenderer
{
    public function __construct(
        private TableNode $tableNode,
    ) {}

    public function render(): string
    {
        $cols = 0;

        $rows = [];
        foreach ($this->tableNode->getData() as $row) {
            $rowMarkDown = '';
            $cols = max($cols, count($row->getColumns()));

            foreach ($row->getColumns() as $n => $col) {
                $rowMarkDown .= '| ' . $col->render();
            }

            $rowMarkDown .= ' |' . "\n";
            $rows[]  = $rowMarkDown;
        }

        return implode("\n", $rows);
    }
}
