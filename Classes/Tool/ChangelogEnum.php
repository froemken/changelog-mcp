<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tool;

enum ChangelogEnum: string
{
    case BUGFIX = 'bugfix';
    case DEPRECATED = 'deprecated';
    case FEATURE = 'feature';
    case IMPORTANT = 'important';
}
