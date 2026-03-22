<?php


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
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
    --palette--;;config,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
    --palette--;;access',
];
