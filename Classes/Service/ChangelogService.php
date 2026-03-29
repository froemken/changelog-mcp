<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ChangelogService
{
    private const ORIGINAL_TYPO3_CHANGELOG_DIRECTORY = 'EXT:core/Documentation/Changelog/';

    /**
     * Needed to convert rst files to Markdown
     */
    public function getAllOriginalTypo3ChangelogFiles(): array
    {
        return GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            GeneralUtility::getFileAbsFileName(self::ORIGINAL_TYPO3_CHANGELOG_DIRECTORY),
            'rst',
            false,
            2,
            '(Howto.rst)',
        );
    }

    public function getChangelog(string $absFile): ?Changelog
    {
        if (@is_file($absFile) === false || !is_readable($absFile)) {
            return null;
        }

        if ($content = file_get_contents($absFile)) {
            return new Changelog(
                $this->cleanUpChangelogContent($content),
                $absFile,
            );
        }

        return null;
    }

    private function cleanUpChangelogContent(string $content): string
    {
        // Doctrine RST parser only allows one space directives
        $content = str_replace(
            [
                '..  include::',
                '..  code-block::',
                '..  warning:',
            ],
            [
                '.. include::',
                '.. code-block::',
                '.. warning:',
            ],
            $content,
        );

        // Lower case directives
        $content = str_replace(
            [
                '.. Important::',
            ],
            [
                '.. important::',
            ],
            $content,
        );

        // Remove ReST includes
        return (string)preg_replace_callback(
            '/^\.\. include:: (.+)$/m',
            function ($match): string {
                return '';
            },
            $content,
        );
    }
}
