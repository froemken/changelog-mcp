<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown;

use Doctrine\RST\Nodes\DocumentNode;
use Doctrine\RST\Parser;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\ParserFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ParserFactoryTest extends UnitTestCase
{
    #[Test]
    public function getParserReturnsConfiguredParserInstance(): void
    {
        $factory = new ParserFactory();
        $parser = $factory->getParser();

        self::assertInstanceOf(Parser::class, $parser);

        // Verify parsing functionality
        $rst = "Header\n======\n\nParagraph text.";
        $document = $parser->parse($rst);

        self::assertInstanceOf(DocumentNode::class, $document);
        $rendered = $document->render();
        self::assertStringContainsString('Header', $rendered);
        self::assertStringContainsString('Paragraph text.', $rendered);
    }

    #[Test]
    public function getParserParsesReStWithUnknownAndCustomReferencesWithoutException(): void
    {
        $factory = new ParserFactory();
        $parser = $factory->getParser();

        $rst = "Header\n======\n\nSee :http:`GET /test` and :https:`https://example.com` and :arbitrary-custom:`custom value`.";
        $document = $parser->parse($rst);

        self::assertInstanceOf(DocumentNode::class, $document);
        $rendered = $document->render();
        self::assertStringContainsString('GET /test', $rendered);
        self::assertStringContainsString('https://example.com', $rendered);
        self::assertStringContainsString('custom value', $rendered);
    }
}
