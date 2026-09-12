<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown\Directive;

use Doctrine\RST\Directives\SubDirective;
use Doctrine\RST\Nodes\Node;
use Doctrine\RST\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Attention;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Hint;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Important;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Note;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\SeeAlso;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Tip;
use StefanFroemken\ChangelogMcp\MarkDown\Directive\Warning;
use StefanFroemken\ChangelogMcp\MarkDown\Node\AdmonitionNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AdmonitionDirectivesTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: SubDirective, 1: string, 2: string}>
     */
    public static function admonitionDirectivesDataProvider(): array
    {
        return [
            'Attention' => [new Attention(), 'attention', '> [!CAUTION]'],
            'Hint' => [new Hint(), 'hint', '> [!TIP]'],
            'Important' => [new Important(), 'important', '> [!IMPORTANT]'],
            'Note' => [new Note(), 'note', '> [!NOTE]'],
            'SeeAlso' => [new SeeAlso(), 'seealso', '> [!NOTE]'],
            'Tip' => [new Tip(), 'tip', '> [!TIP]'],
            'Warning' => [new Warning(), 'warning', '> [!WARNING]'],
        ];
    }

    #[Test]
    #[DataProvider('admonitionDirectivesDataProvider')]
    public function directiveHasCorrectNameAndRendersExpectedAlertHeader(
        SubDirective $directive,
        string $expectedName,
        string $expectedHeaderPrefix,
    ): void {
        self::assertSame($expectedName, $directive->getName());

        $parserMock = $this->createMock(Parser::class);
        $documentMock = $this->createMock(Node::class);
        $documentMock->method('render')->willReturn('Test admonition content');

        $node = $directive->processSub($parserMock, $documentMock, '', '', []);

        self::assertInstanceOf(AdmonitionNode::class, $node);
        $rendered = $node->render();
        self::assertStringStartsWith($expectedHeaderPrefix, $rendered);
        self::assertStringContainsString('> Test admonition content', $rendered);
    }

    /**
     * @return array<string, array{0: SubDirective}>
     */
    public static function admonitionInstancesDataProvider(): array
    {
        return [
            'Attention' => [new Attention()],
            'Hint' => [new Hint()],
            'Important' => [new Important()],
            'Note' => [new Note()],
            'SeeAlso' => [new SeeAlso()],
            'Tip' => [new Tip()],
            'Warning' => [new Warning()],
        ];
    }

    #[Test]
    #[DataProvider('admonitionInstancesDataProvider')]
    public function processSubReturnsNullWhenDocumentIsNull(SubDirective $directive): void
    {
        $parserMock = $this->createMock(Parser::class);
        $result = $directive->processSub($parserMock, null, '', '', []);

        self::assertNull($result);
    }
}
