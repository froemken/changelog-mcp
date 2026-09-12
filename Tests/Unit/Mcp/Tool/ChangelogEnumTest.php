<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Mcp\Tool;

use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\Mcp\Tool\ChangelogEnum;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ChangelogEnumTest extends UnitTestCase
{
    #[Test]
    public function enumCasesHaveExpectedValues(): void
    {
        self::assertSame('breaking', ChangelogEnum::BREAKING->value);
        self::assertSame('deprecation', ChangelogEnum::DEPRECATION->value);
        self::assertSame('feature', ChangelogEnum::FEATURE->value);
        self::assertSame('important', ChangelogEnum::IMPORTANT->value);
    }
}
