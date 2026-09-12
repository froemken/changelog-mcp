<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Directive;

use Doctrine\RST\Directives\Directive;
use Doctrine\RST\Nodes\Node;
use Doctrine\RST\Parser;

final class CsvTable extends Directive
{
    public function getName(): string
    {
        return 'csv-table';
    }

    /**
     * @param string[] $options
     */
    public function process(
        Parser $parser,
        ?Node $node,
        string $variable,
        string $data,
        array $options,
    ): void {
        if (!$node instanceof Node) {
            return;
        }

        $lines = explode("\n", trim($node->getValue()));
        $rows = [];
        if (!empty($options['header'])) {
            $rows[] = str_getcsv($options['header']);
        }
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $rows[] = str_getcsv($trimmed);
            }
        }

        if ($rows === []) {
            return;
        }

        $md = '';
        if ($data !== '') {
            $md .= '**' . trim($data) . "**\n\n";
        }
        $headerRow = array_shift($rows);
        $colCount = count($headerRow);
        $md .= '| ' . implode(' | ', array_map(trim(...), $headerRow)) . " |\n";
        $md .= '| ' . implode(' | ', array_fill(0, $colCount, '---')) . " |\n";
        foreach ($rows as $row) {
            $row = array_pad($row, $colCount, '');
            $md .= '| ' . implode(' | ', array_map(trim(...), $row)) . " |\n";
        }
        $md .= "\n";

        $parser->getDocument()->addNode($parser->getNodeFactory()->createRawNode($md));
    }
}
