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

final class ListTable extends Directive
{
    public function getName(): string
    {
        return 'list-table';
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

        $rows = $this->parseRows((string)$node->getValue());
        if ($rows === []) {
            return;
        }

        $md = $this->buildMarkdown($rows, $data, $options);
        if ($md === '') {
            return;
        }

        $parser->getDocument()->addNode($parser->getNodeFactory()->createRawNode($md));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseRows(string $content): array
    {
        $lines = explode("\n", trim($content));
        $rows = [];
        $currentRow = -1;
        $currentCol = -1;

        foreach ($lines as $line) {
            $this->parseLine($line, $rows, $currentRow, $currentCol);
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function parseLine(
        string $line,
        array &$rows,
        int &$currentRow,
        int &$currentCol,
    ): void {
        if (preg_match('/^\s*\*\s*-\s*(.*)$/', $line, $matches) === 1) {
            $currentRow++;
            $currentCol = 0;
            $rows[$currentRow] = [$matches[1]];
        } elseif (preg_match('/^\s*\*\s*$/', $line) === 1) {
            $currentRow++;
            $currentCol = -1;
            $rows[$currentRow] = [];
        } elseif (preg_match('/^\s*-\s*(.*)$/', $line, $matches) === 1) {
            $this->startNewColumn($rows, $currentRow, $currentCol, $matches[1]);
        } else {
            $this->appendCellContent($rows, $currentRow, $currentCol, $line);
        }
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function startNewColumn(
        array &$rows,
        int &$currentRow,
        int &$currentCol,
        string $cellContent,
    ): void {
        if ($currentRow === -1) {
            $currentRow = 0;
            $rows[$currentRow] = [];
        }
        $currentCol++;
        $rows[$currentRow][] = $cellContent;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function appendCellContent(
        array &$rows,
        int $currentRow,
        int $currentCol,
        string $line,
    ): void {
        $trimmed = trim($line);
        if ($trimmed === '' || $currentRow < 0 || $currentCol < 0) {
            return;
        }
        $rows[$currentRow][$currentCol] .= ' ' . $trimmed;
    }

    /**
     * @param array<int, array<int, string>> $rows
     * @param string[] $options
     */
    private function buildMarkdown(array $rows, string $data, array $options): string
    {
        $colCount = 0;
        foreach ($rows as $row) {
            $colCount = max($colCount, count($row));
        }

        if ($colCount === 0) {
            return '';
        }

        $hasHeader = !isset($options['header-rows']) || (int)$options['header-rows'] > 0;
        $headerRow = $hasHeader ? (array_shift($rows) ?? []) : [];
        $headerRow = array_pad($headerRow, $colCount, '');

        $md = $data !== '' ? '**' . trim($data) . "**\n\n" : '';
        $md .= $this->renderRow($headerRow);
        $md .= '| ' . implode(' | ', array_fill(0, $colCount, '---')) . " |\n";
        foreach ($rows as $row) {
            $md .= $this->renderRow(array_pad($row, $colCount, ''));
        }

        return $md . "\n";
    }

    /**
     * @param list<string> $columns
     */
    private function renderRow(array $columns): string
    {
        $cleaned = array_map($this->cleanCell(...), $columns);
        return '| ' . implode(' | ', $cleaned) . " |\n";
    }

    private function cleanCell(string $cell): string
    {
        $cell = trim($cell);
        return (string)preg_replace('/(?<!\\\\)\|/', '\\|', $cell);
    }
}
