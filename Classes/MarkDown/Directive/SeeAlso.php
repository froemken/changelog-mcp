<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown\Directive;

final class SeeAlso extends AbstractAdmonitionDirective
{
    public function getName(): string
    {
        return 'seealso';
    }

    protected function getAlertTag(): string
    {
        return 'NOTE';
    }
}
