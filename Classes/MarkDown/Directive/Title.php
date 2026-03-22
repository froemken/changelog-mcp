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

/**
 * Add index to document
 *
 * .. index:: PHP-API, ext:extbase
 */
final class Title extends Directive
{
    public function getName(): string
    {
        return 'title';
    }

    /**
     * @param string[] $options
     */
    public function process(
        Parser $parser,
        ?Node $node,
        string $variable,
        string $data,
        array $options
    ): void {
        $document = $parser->getDocument();

        $document->addHeaderNode(
            $parser->getNodeFactory()->createRawNode('\title{' . $data . '}')
        );

        if ($node === null) {
            return;
        }

        $document->addNode($node);
    }
}
