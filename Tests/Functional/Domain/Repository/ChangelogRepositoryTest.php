<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use StefanFroemken\ChangelogMcp\Service\Changelog;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ChangelogRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'changelog_mcp',
    ];

    private ChangelogRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(ChangelogRepository::class);
    }

    #[Test]
    public function createAndGetChangelogContentByUid(): void
    {
        $changelog = new Changelog(
            'RST source',
            "# Feature: #10001 - My Super Feature\n\n## Description\nFeature description.",
            '/var/www/core/14.0/feature-10001-my-super-feature.rst',
        );

        $this->subject->create($changelog);

        $results = $this->subject->getChangelogs('Super Feature');
        self::assertCount(1, $results);
        $uid = (int)$results[0]['uid'];

        $content = $this->subject->getChangelogContentByUid($uid);
        self::assertNotNull($content);
        self::assertStringContainsString('My Super Feature', $content);
    }

    #[Test]
    public function truncateRemovesAllEntries(): void
    {
        $changelog = new Changelog(
            '',
            "# Breaking: #10002 - Some Removal\n\n## Description\nDesc",
            '/var/www/core/14.0/breaking-10002-some-removal.rst',
        );
        $this->subject->create($changelog);

        self::assertNotEmpty($this->subject->getChangelogs(null));

        $this->subject->truncate();

        self::assertEmpty($this->subject->getChangelogs(null));
    }

    #[Test]
    public function getChangelogsFiltersByChangeType(): void
    {
        $this->subject->create(new Changelog('', "# Feature: #101 - Feature One\n\nDesc", '/core/14.0/feature-101-one.rst'));
        $this->subject->create(new Changelog('', "# Breaking: #102 - Breaking One\n\nDesc", '/core/14.0/breaking-102-two.rst'));

        $features = $this->subject->getChangelogs(null, null, 'feature');
        self::assertCount(1, $features);
        self::assertSame('feature', $features[0]['change_type']);
        self::assertSame('Feature One', $features[0]['title']);

        $breakings = $this->subject->getChangelogs(null, null, 'breaking');
        self::assertCount(1, $breakings);
        self::assertSame('breaking', $breakings[0]['change_type']);
    }

    #[Test]
    public function getChangelogsFiltersByVersionStrictlyWhenNoPromptGiven(): void
    {
        $this->subject->create(new Changelog('', "# Feature: #201 - V13 Feature\n\nDesc", '/core/13.4/feature-201-v13.rst'));
        $this->subject->create(new Changelog('', "# Feature: #202 - V14 Feature\n\nDesc", '/core/14.0/feature-202-v14.rst'));

        $v13Results = $this->subject->getChangelogs(null, '13');
        self::assertCount(1, $v13Results);
        self::assertSame('13.4', $v13Results[0]['version_string']);

        $v14Results = $this->subject->getChangelogs(null, '14.0');
        self::assertCount(1, $v14Results);
        self::assertSame('14.0', $v14Results[0]['version_string']);
    }

    #[Test]
    public function getChangelogsSupportsIntelligentVersionCompatibilityWithSearchPrompt(): void
    {
        $this->subject->create(new Changelog('', "# Feature: #301 - Mailer Api\n\nDesc", '/core/12.4/feature-301-mailer.rst'));
        $this->subject->create(new Changelog('', "# Feature: #302 - Mailer Api Next\n\nDesc", '/core/14.1/feature-302-mailer.rst'));

        // Query targeting TYPO3 13.0 should include 12.4, but exclude 14.1
        $results = $this->subject->getChangelogs('Mailer Api', '13.0');
        self::assertCount(1, $results);
        self::assertSame('12.4', $results[0]['version_string']);

        // Query targeting TYPO3 14.2 should include both 12.4 and 14.1
        $results14 = $this->subject->getChangelogs('Mailer Api', '14.2');
        self::assertCount(2, $results14);
    }

    #[Test]
    public function getChangelogsAppliesIntentScoringForKeywords(): void
    {
        $this->subject->create(new Changelog('', "# Breaking: #401 - Database Connection removed\n\nDesc", '/core/14.0/breaking-401-db.rst'));
        $this->subject->create(new Changelog('', "# Feature: #402 - Database Connection implement\n\nDesc", '/core/14.0/feature-402-db.rst'));

        // 'removed' keyword triggers isBreakingIntent (+50 for Breaking)
        $results = $this->subject->getChangelogs('Database Connection removed');
        self::assertNotEmpty($results);
        self::assertSame('breaking', $results[0]['change_type']);

        // 'implement' keyword triggers isFeatureIntent (+50 for Feature)
        $resultsFeature = $this->subject->getChangelogs('Database Connection implement');
        self::assertNotEmpty($resultsFeature);
        self::assertSame('feature', $resultsFeature[0]['change_type']);
    }
}
