<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Directive;

use Doctrine\RST\Directives\SubDirective;
use Doctrine\RST\Nodes\Node;
use Doctrine\RST\Parser;
use StefanFroemken\ChangelogMcp\MarkDown\Node\AdmonitionNode;

abstract class AbstractAdmonitionDirective extends SubDirective
{
    abstract protected function getAlertTag(): string;

    public function processSub(
        Parser $parser,
        ?Node $document,
        string $variable,
        string $data,
        array $options,
    ): ?Node {
        if ($document === null) {
            return null;
        }

        $header = '> [!' . $this->getAlertTag() . "]\n";

        return new AdmonitionNode($document, $header);
    }
}
