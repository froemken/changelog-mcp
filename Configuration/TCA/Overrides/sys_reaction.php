<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

use StefanFroemken\ChangelogMcp\Reaction\ChangelogMcpReaction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTcaSelectItem(
    'sys_reaction',
    'reaction_type',
    [
        'label' => ChangelogMcpReaction::getDescription(),
        'value' => ChangelogMcpReaction::getType(),
        'icon' => ChangelogMcpReaction::getIconIdentifier(),
    ],
);

$GLOBALS['TCA']['sys_reaction']['types'][ChangelogMcpReaction::getType()] = [
    'showitem' => '
    --div--;core.form.tabs:general,
    --palette--;;config,
    --div--;core.form.tabs:access,
    --palette--;;access',
];
