<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\MarkDown;

use Doctrine\RST\Configuration;
use Doctrine\RST\Formats\InternalFormat;
use Doctrine\RST\Kernel;
use Doctrine\RST\Parser;
use Doctrine\RST\References;
use Doctrine\RST\Templates\TwigTemplateRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ParserFactory
{
    private const TEMPLATE_PATH = 'EXT:changelog_mcp/Resources/Private/Templates';

    public function getParser(): Parser
    {
        $configuration = $this->getConfiguration();

        $templateRenderer = new TwigTemplateRenderer($configuration);

        $configuration->addFormat(new InternalFormat(new MarkDownFormat($templateRenderer)));

        $kernel = $this->getKernel($configuration);

        $parser = new Parser($kernel);

        // Do not follow includes
        $parser->setIncludePolicy(false);

        return $parser;
    }

    private function getConfiguration(): Configuration
    {
        $configuration = new Configuration();

        // Ignore un-resolvable references
        $configuration->setIgnoreInvalidReferences(true);

        // Pre configure "md" formatter
        $configuration->setOutputFormat('md');

        // Set file extension
        $configuration->setFileExtension('md');

        // Doctrine Parser cannot differ between:
        // =====
        // Hello
        // =====
        //
        // and
        //
        // Hello
        // =====
        //
        // So both are the highest header for it.
        // We try to identify the highest header on our own, so the initial header level is 2
        $configuration->setInitialHeaderLevel(2);

        // Sorry, don't want to have a lower case "default" directory in the "Template" path ;-)
        $configuration->setTheme('Default');

        // Add the template path to our twig templates to build "md" files
        $configuration->addCustomTemplateDir(
            GeneralUtility::getFileAbsFileName(self::TEMPLATE_PATH),
        );

        return $configuration;
    }

    private function getKernel(Configuration $configuration): Kernel
    {
        return new Kernel(
            $configuration,
            [],
            [
                new References\Doc('abbr', false),
                new References\Doc('aspect', false),
                new References\Doc('bash', false),
                new References\Doc('class', false),
                new References\Doc('code', false),
                new References\Doc('composer', false),
                new References\Doc('confval', false),
                new References\Doc('csp', false),
                new References\Doc('css', false),
                new References\Doc('directory', false),
                new References\Doc('EXT', false),
                new References\Doc('file', false),
                new References\Doc('fluid', false),
                new References\Doc('folder', false),
                new References\Doc('guilabel', false),
                new References\Doc('html', false),
                new References\Doc('issue', false),
                new References\Doc('javascript', false),
                new References\Doc('js', false),
                new References\Doc('json', false),
                new References\Doc('kbd', false),
                new References\Doc('path', false),
                new References\Doc('php', false),
                new References\Doc('PHP', false),
                new References\Doc('quote', false),
                new References\Doc('samp', false),
                new References\Doc('shell', false),
                new References\Doc('sql', false),
                new References\Doc('tsconfig', false),
                new References\Doc('typoscript', false),
                new References\Doc('xml', false),
                new References\Doc('yaml', false),
            ],
        );
    }
}
