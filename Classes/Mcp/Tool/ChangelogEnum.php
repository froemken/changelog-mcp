<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Mcp\Tool;

enum ChangelogEnum: string
{
    case BREAKING = 'breaking';
    case DEPRECATION = 'deprecation';
    case FEATURE = 'feature';
    case IMPORTANT = 'important';
}
