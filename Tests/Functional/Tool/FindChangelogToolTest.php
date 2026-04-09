<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Functional\Tool;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FindChangelogToolTest extends FunctionalTestCase
{
    protected ClientFactory $subject;

    protected ExtConf $extConf;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/maps2',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->extConf,
        );

        parent::tearDown();
    }

    #[Test]
    public function createCreatesGoogleMapsClient(): void
    {
    }
}
