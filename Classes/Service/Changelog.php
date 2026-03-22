<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class Changelog
{
    public function __construct(
        private string $content,
        private string $absFile,
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAbsFile(): string
    {
        return $this->absFile;
    }

    public function getFilename(): string
    {
        return basename($this->absFile);
    }

    public function getMarkDownFilename(): string
    {
        $parts = GeneralUtility::split_fileref($this->absFile);

        return $parts['filebody'] . '.md';
    }

    /**
     * Will return 11.3, 13.4, 10.4
     */
    public function getTypo3Version(): string
    {
        return str_replace('.x', '', basename(dirname($this->absFile)));
    }

    public function getType(): string
    {
        $parts = explode('-', $this->getFilename());

        return $parts[0] ?? '';
    }

    public function getIssue(): int
    {
        $parts = explode('-', $this->getFilename());

        return (int)($parts[1] ?? 0);
    }
}
