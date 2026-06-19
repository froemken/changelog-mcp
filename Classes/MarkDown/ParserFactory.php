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
use Doctrine\RST\Templates\TwigTemplateRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Reference\GenericReference;
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
                new GenericReference('abbr'),
                new GenericReference('aspect'),
                new GenericReference('bash'),
                new GenericReference('class'),
                new GenericReference('code'),
                new GenericReference('composer'),
                new GenericReference('confval'),
                new GenericReference('csp'),
                new GenericReference('css'),
                new GenericReference('directory'),
                new GenericReference('EXT'),
                new GenericReference('file'),
                new GenericReference('fluid'),
                new GenericReference('folder'),
                new GenericReference('guilabel'),
                new GenericReference('html'),
                new GenericReference('issue'),
                new GenericReference('javascript'),
                new GenericReference('js'),
                new GenericReference('json'),
                new GenericReference('kbd'),
                new GenericReference('path'),
                new GenericReference('php'),
                new GenericReference('PHP'),
                new GenericReference('quote'),
                new GenericReference('samp'),
                new GenericReference('shell'),
                new GenericReference('sql'),
                new GenericReference('tsconfig'),
                new GenericReference('typoscript'),
                new GenericReference('xml'),
                new GenericReference('yaml'),
            ],
        );
    }
}
