<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'MCP for TYPO3 Changelogs',
    'description' => 'Catagogue the TYPO3 contained changelogs and provide information via MCP',
    'category' => 'services',
    'state' => 'alpha',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'author_company' => '',
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];