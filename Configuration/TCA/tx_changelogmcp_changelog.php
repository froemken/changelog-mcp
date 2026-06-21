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

return [
    'ctrl' => [
        'title' => 'changelog_mcp.db.:tx_changelogmcp_changelog',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,tags,content',
        'iconfile' => 'EXT:changelog_mcp/Resources/Public/Icons/tx_changelogmcp_changelog.svg',
    ],
    'types' => [
        0 => [
            'showitem' => 'hidden, title, change_type, version_string, major_version, issue_number, tags, summary, content',
        ],
    ],
    'palettes' => [],
    'columns' => [
        'title' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
                'max' => 255,
            ],
        ],
        'change_type' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.change_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['changelog_mcp.db.:tx_changelogmcp_changelog.change_type.feature', 'feature'],
                    ['changelog_mcp.db.:tx_changelogmcp_changelog.change_type.deprecation', 'deprecation'],
                    ['changelog_mcp.db.:tx_changelogmcp_changelog.change_type.important', 'important'],
                    ['changelog_mcp.db.:tx_changelogmcp_changelog.change_type.bugfix', 'bugfix'],
                ],
                'default' => 'feature',
            ],
        ],
        'version_string' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.version_string',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim,required',
                'max' => 20,
            ],
        ],
        'major_version' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.major_version',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int,required',
                'range' => [
                    'lower' => 0,
                ],
                'default' => 0,
            ],
        ],
        'issue_number' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.issue_number',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'range' => [
                    'lower' => 0,
                ],
            ],
        ],
        'tags' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.tags',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
                'eval' => 'trim',
            ],
        ],
        'summary' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.summary',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 5,
                'eval' => 'trim',
            ],
        ],
        'content' => [
            'exclude' => false,
            'label' => 'changelog_mcp.db.:tx_changelogmcp_changelog.content',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true, // Optional: Wenn du einen Rich-Text-Editor für Markdown möchtest
                'richtextConfiguration' => 'default', // Oder eine spezifische Konfiguration
                'cols' => 80,
                'rows' => 15,
                'eval' => 'trim',
            ],
        ],
    ],
];
