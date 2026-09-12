<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown;

use Doctrine\RST\Directives\Directive;
use Doctrine\RST\Nodes\AnchorNode;
use Doctrine\RST\Nodes\CodeNode;
use Doctrine\RST\Nodes\DocumentNode;
use Doctrine\RST\Nodes\ImageNode;
use Doctrine\RST\Nodes\ListNode;
use Doctrine\RST\Nodes\ParagraphNode;
use Doctrine\RST\Nodes\QuoteNode;
use Doctrine\RST\Nodes\SeparatorNode;
use Doctrine\RST\Nodes\SpanNode;
use Doctrine\RST\Nodes\TableNode;
use Doctrine\RST\Nodes\TitleNode;
use Doctrine\RST\Templates\TemplateRenderer;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\MarkDownFormat;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MarkDownFormatTest extends UnitTestCase
{
    #[Test]
    public function getFileExtensionReturnsMd(): void
    {
        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $format = new MarkDownFormat($templateRenderer);

        self::assertSame('md', $format->getFileExtension());
    }

    #[Test]
    public function getDirectivesReturnsRegisteredDirectives(): void
    {
        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $format = new MarkDownFormat($templateRenderer);

        $directives = $format->getDirectives();
        self::assertCount(17, $directives);
        self::assertContainsOnlyInstancesOf(Directive::class, $directives);
    }

    #[Test]
    public function getNodeRendererFactoriesRegistersAllRequiredNodes(): void
    {
        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $format = new MarkDownFormat($templateRenderer);

        $factories = $format->getNodeRendererFactories();

        $expectedNodes = [
            AnchorNode::class,
            CodeNode::class,
            DocumentNode::class,
            ImageNode::class,
            ListNode::class,
            ParagraphNode::class,
            QuoteNode::class,
            SeparatorNode::class,
            SpanNode::class,
            TableNode::class,
            TitleNode::class,
        ];

        foreach ($expectedNodes as $nodeClass) {
            self::assertArrayHasKey($nodeClass, $factories);
        }
    }
}
