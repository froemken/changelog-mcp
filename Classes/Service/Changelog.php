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
    public function getVersionString(): string
    {
        return str_replace('.x', '', basename(dirname($this->absFile)));
    }

    public function getChangeType(): string
    {
        $parts = explode('-', $this->getFilename());

        return $parts[0] ?? '';
    }

    /**
     * Extracts the issue number from the filename.
     * Example: "feature-12345-some-title.rst" -> 12345
     */
    public function getIssueNumber(): int
    {
        $parts = explode('-', $this->getFilename());

        return (int)($parts[1] ?? 0);
    }

    /**
     * Extracts the major version from the version string.
     * Example: "14.1" -> 14
     */
    public function getMajorVersion(): int
    {
        $versionString = $this->getVersionString();

        return (int)explode('.', $versionString)[0];
    }

    /**
     * Currently, tags are not parsed from the content, so this returns null.
     */
    public function getTags(): ?string
    {
        return null;
    }

    /**
     * Extracts the title from the Markdown content.
     * It searches for the first line starting with '#', then removes Markdown heading markers,
     * and any arbitrary text followed by the issue number pattern (e.g., "#59659 - ").
     */
    public function getTitle(): string
    {
        $lines = explode("\n", $this->content);
        $titleLine = '';

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                $titleLine = trim($line);
                break;
            }
        }

        if ($titleLine === '') {
            return '';
        }

        // Remove Markdown heading markers (e.g., #, ##, ###)
        $titleLine = preg_replace('/^#+\s*/', '', $titleLine);
        $titleLine = trim($titleLine);

        // Remove everything before the actual title:
        // This includes any arbitrary text (like "Breaking:", "Feature:", "Currywurst", etc.)
        // followed by the issue number pattern (#<number> - )
        // The non-greedy .*? ensures it matches the shortest possible string until the issue number.
        // This handles cases like:
        // "Breaking: #59659 - Removal of deprecated code..."
        // "Feature: #12345 - New feature..."
        // "Currywurst: #99999 - Some random text..."
        $titleLine = preg_replace('/^.*?#\d+\s*-\s*/', '', $titleLine);

        return trim($titleLine);
    }
}
