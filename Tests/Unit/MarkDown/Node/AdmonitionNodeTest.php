<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\MarkDown\Node;

use Doctrine\RST\Nodes\Node;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\MarkDown\Node\AdmonitionNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AdmonitionNodeTest extends UnitTestCase
{
    #[Test]
    public function renderFormatsSingleLineContent(): void
    {
        $documentMock = $this->createMock(Node::class);
        $documentMock->method('render')->willReturn('Be careful when updating.');

        $node = new AdmonitionNode($documentMock, "> [!WARNING]\n");

        $expected = "> [!WARNING]\n> Be careful when updating.\n\n";
        self::assertSame($expected, $node->render());
    }

    #[Test]
    public function renderFormatsMultiLineContentWithEmptyLines(): void
    {
        $documentMock = $this->createMock(Node::class);
        $documentMock->method('render')->willReturn("First paragraph.\n\nSecond paragraph line 1.\nSecond paragraph line 2.");

        $node = new AdmonitionNode($documentMock, "> [!NOTE]\n");

        $expected = "> [!NOTE]\n> First paragraph.\n>\n> Second paragraph line 1.\n> Second paragraph line 2.\n\n";
        self::assertSame($expected, $node->render());
    }
}
