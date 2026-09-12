<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown\Renderer;

use Doctrine\RST\Environment;
use Doctrine\RST\Nodes\AnchorNode;
use Doctrine\RST\Nodes\CodeNode;
use Doctrine\RST\Nodes\DocumentNode;
use Doctrine\RST\Nodes\ImageNode;
use Doctrine\RST\Nodes\ListNode;
use Doctrine\RST\Nodes\Node;
use Doctrine\RST\Nodes\ParagraphNode;
use Doctrine\RST\Nodes\QuoteNode;
use Doctrine\RST\Nodes\SpanNode;
use Doctrine\RST\Nodes\TableNode;
use Doctrine\RST\Nodes\TitleNode;
use Doctrine\RST\Templates\TemplateRenderer;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\AnchorNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\CodeNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\DocumentNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\ImageNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\ListNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\ParagraphNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\QuoteNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\SeparatorNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\TableNodeRenderer;
use StefanFroemken\ChangelogMcp\MarkDown\Renderer\TitleNodeRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NodeRenderersTest extends UnitTestCase
{
    #[Test]
    public function anchorNodeRendererDelegatesToTemplate(): void
    {
        $anchorNode = new AnchorNode('my-anchor');
        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->once())
            ->method('render')
            ->with('anchor.md.twig', ['anchorNode' => $anchorNode])
            ->willReturn('<a id="my-anchor"></a>');

        $renderer = new AnchorNodeRenderer($anchorNode, $templateRenderer);
        self::assertSame('<a id="my-anchor"></a>', $renderer->render());
    }

    #[Test]
    public function codeNodeRendererRendersRawOrTemplate(): void
    {
        $rawCodeNode = new CodeNode(['echo "raw";']);
        $rawCodeNode->setRaw(true);

        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->never())->method('render');

        $rawRenderer = new CodeNodeRenderer($rawCodeNode, $templateRenderer);
        self::assertSame('echo "raw";', $rawRenderer->render());

        $highlightedCodeNode = new CodeNode(['$foo = "bar";']);
        $templateRenderer2 = $this->createMock(TemplateRenderer::class);
        $templateRenderer2->expects($this->once())
            ->method('render')
            ->with('code.md.twig', ['codeNode' => $highlightedCodeNode])
            ->willReturn("```php\n\$foo = \"bar\";\n```");

        $highlightedRenderer = new CodeNodeRenderer($highlightedCodeNode, $templateRenderer2);
        self::assertSame("```php\n\$foo = \"bar\";\n```", $highlightedRenderer->render());
    }

    #[Test]
    public function documentNodeRendererRendersDocumentWrapper(): void
    {
        $environment = self::createStub(Environment::class);
        $document = new DocumentNode($environment);

        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->once())
            ->method('render')
            ->with('document.md.twig', ['body' => ''])
            ->willReturn('# Full Document');

        $renderer = new DocumentNodeRenderer($document, $templateRenderer);
        self::assertSame('# Full Document', $renderer->renderDocument());
    }

    #[Test]
    public function imageNodeRendererDelegatesToTemplate(): void
    {
        $imageNode = new ImageNode('path/to/image.png');
        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->once())
            ->method('render')
            ->with('image.md.twig', ['imageNode' => $imageNode])
            ->willReturn('![alt](path/to/image.png)');

        $renderer = new ImageNodeRenderer($imageNode, $templateRenderer);
        self::assertSame('![alt](path/to/image.png)', $renderer->render());
    }

    #[Test]
    public function listNodeRendererDistinguishesOrderedAndUnordered(): void
    {
        $unorderedList = new ListNode([], false);
        $orderedList = new ListNode([], true);

        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->exactly(2))
            ->method('render')
            ->willReturnMap([
                ['bullet-list.md.twig', ['listNode' => $unorderedList], "- item 1\n- item 2"],
                ['enumerated-list.md.twig', ['listNode' => $orderedList], "1. item 1\n2. item 2"],
            ]);

        $unorderedRenderer = new ListNodeRenderer($unorderedList, $templateRenderer);
        self::assertSame("- item 1\n- item 2", $unorderedRenderer->render());

        $orderedRenderer = new ListNodeRenderer($orderedList, $templateRenderer);
        self::assertSame("1. item 1\n2. item 2", $orderedRenderer->render());
    }

    #[Test]
    public function paragraphNodeRendererDelegatesToTemplate(): void
    {
        $spanNode = self::createStub(SpanNode::class);
        $paragraphNode = new ParagraphNode($spanNode);
        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->once())
            ->method('render')
            ->with('paragraph.md.twig', ['paragraphNode' => $paragraphNode])
            ->willReturn("Paragraph text\n");

        $renderer = new ParagraphNodeRenderer($paragraphNode, $templateRenderer);
        self::assertSame("Paragraph text\n", $renderer->render());
    }

    #[Test]
    public function quoteNodeRendererPrefixesLinesWithGreaterThan(): void
    {
        $innerNode = self::createStub(DocumentNode::class);
        $innerNode->method('render')->willReturn("Line 1\nLine 2");

        $quoteNode = new QuoteNode($innerNode);

        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->once())
            ->method('render')
            ->with('quote.md.twig', ['quote' => "> Line 1\n> Line 2"])
            ->willReturn("> Line 1\n> Line 2\n");

        $renderer = new QuoteNodeRenderer($quoteNode, $templateRenderer);
        self::assertSame("> Line 1\n> Line 2\n", $renderer->render());
    }

    #[Test]
    public function separatorNodeRendererDelegatesToTemplate(): void
    {
        $templateRenderer = $this->createMock(TemplateRenderer::class);
        $templateRenderer->expects($this->once())
            ->method('render')
            ->with('separator.md.twig')
            ->willReturn("---\n");

        $renderer = new SeparatorNodeRenderer($templateRenderer);
        self::assertSame("---\n", $renderer->render());
    }

    #[Test]
    public function tableNodeRendererRendersTablePipeFormat(): void
    {
        $col1 = self::createStub(Node::class);
        $col1->method('render')->willReturn('Cell 1');
        $col2 = self::createStub(Node::class);
        $col2->method('render')->willReturn('Cell 2');

        $row = new class ($col1, $col2) {
            /**
             * @var list<Node>
             */
            private readonly array $columns;

            public function __construct(Node ...$columns)
            {
                $this->columns = array_values($columns);
            }

            /**
             * @return list<Node>
             */
            public function getColumns(): array
            {
                return $this->columns;
            }
        };

        $tableNode = self::createStub(TableNode::class);
        $tableNode->method('getData')->willReturn([$row]);

        $renderer = new TableNodeRenderer($tableNode);
        self::assertSame("| Cell 1| Cell 2 |\n", $renderer->render());
    }

    #[Test]
    public function titleNodeRendererComputesIndentationAndOverridesForChangelogTitles(): void
    {
        $titleNodeLevel2 = self::createStub(TitleNode::class);
        $titleNodeLevel2->method('getLevel')->willReturn(2);
        $titleNodeLevel2->method('getValueString')->willReturn('Description');

        $templateRenderer1 = $this->createMock(TemplateRenderer::class);
        $templateRenderer1->expects($this->once())
            ->method('render')
            ->with('title.md.twig', ['levelIndent' => '##', 'titleNode' => $titleNodeLevel2])
            ->willReturn("## Description\n");

        $renderer = new TitleNodeRenderer($titleNodeLevel2, $templateRenderer1);
        self::assertSame("## Description\n", $renderer->render());

        $titleNodeChangelog = self::createStub(TitleNode::class);
        $titleNodeChangelog->method('getLevel')->willReturn(3);
        $titleNodeChangelog->method('getValueString')->willReturn('Breaking: #12345 - Removed hook');

        $templateRenderer2 = $this->createMock(TemplateRenderer::class);
        $templateRenderer2->expects($this->once())
            ->method('render')
            ->with('title.md.twig', ['levelIndent' => '#', 'titleNode' => $titleNodeChangelog])
            ->willReturn("# Breaking: #12345 - Removed hook\n");

        $renderer2 = new TitleNodeRenderer($titleNodeChangelog, $templateRenderer2);
        self::assertSame("# Breaking: #12345 - Removed hook\n", $renderer2->render());
    }
}
