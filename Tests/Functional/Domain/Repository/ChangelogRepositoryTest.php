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
        self::assertSame(1, $results['totalCount']);
        self::assertCount(1, $results['items']);
        $uid = (int)$results['items'][0]['uid'];

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

        self::assertGreaterThan(0, $this->subject->getChangelogs(null)['totalCount']);

        $this->subject->truncate();

        self::assertSame(0, $this->subject->getChangelogs(null)['totalCount']);
        self::assertEmpty($this->subject->getChangelogs(null)['items']);
    }

    #[Test]
    public function getChangelogsFiltersByChangeTypeCaseInsensitively(): void
    {
        $this->subject->create(new Changelog('', "# Feature: #101 - Feature One\n\nDesc", '/core/14.0/feature-101-one.rst'));
        $this->subject->create(new Changelog('', "# Breaking: #102 - Breaking One\n\nDesc", '/core/14.0/breaking-102-two.rst'));

        $features = $this->subject->getChangelogs(null, null, null, 'feature');
        self::assertSame(1, $features['totalCount']);
        self::assertSame('Feature', $features['items'][0]['change_type']);
        self::assertSame('Feature One', $features['items'][0]['title']);

        $breakings = $this->subject->getChangelogs(null, null, null, 'BREAKING');
        self::assertSame(1, $breakings['totalCount']);
        self::assertSame('Breaking', $breakings['items'][0]['change_type']);
    }

    #[Test]
    public function getChangelogsFiltersByVersionRange(): void
    {
        $this->subject->create(new Changelog('', "# Feature: #201 - V10 Feature\n\nDesc", '/core/10.4/feature-201-v10.rst'));
        $this->subject->create(new Changelog('', "# Feature: #202 - V11 Feature\n\nDesc", '/core/11.5/feature-202-v11.rst'));
        $this->subject->create(new Changelog('', "# Feature: #203 - V12 Feature\n\nDesc", '/core/12.4/feature-203-v12.rst'));
        $this->subject->create(new Changelog('', "# Feature: #204 - V13 Feature\n\nDesc", '/core/13.4/feature-204-v13.rst'));
        $this->subject->create(new Changelog('', "# Feature: #205 - V14 Feature\n\nDesc", '/core/14.0/feature-205-v14.rst'));

        // Range from 10 to 13: includes 10, 11, 12, 13 (excludes 14)
        $rangeResults = $this->subject->getChangelogs(null, '10', '13');
        self::assertSame(4, $rangeResults['totalCount']);
        $majorVersions = array_column($rangeResults['items'], 'major_version');
        self::assertNotContains(14, $majorVersions);
        self::assertContains(10, $majorVersions);
        self::assertContains(13, $majorVersions);

        // Exact single version: from 14 to 14
        $singleVersionResults = $this->subject->getChangelogs(null, '14', '14');
        self::assertSame(1, $singleVersionResults['totalCount']);
        self::assertSame('14.0', $singleVersionResults['items'][0]['version_string']);

        // Upper bound only: up to 11
        $toResults = $this->subject->getChangelogs(null, null, '11');
        self::assertSame(2, $toResults['totalCount']);

        // Lower bound only: from 12 onwards
        $fromResults = $this->subject->getChangelogs(null, '12');
        self::assertSame(3, $fromResults['totalCount']);
    }

    #[Test]
    public function getChangelogsAppliesPaginationWithLimitAndOffset(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->subject->create(
                new Changelog('', sprintf("# Feature: #30%d - Page Item %d\n\nDesc", $i, $i), sprintf('/core/14.0/feature-30%d.rst', $i)),
            );
        }

        $page1 = $this->subject->getChangelogs(null, null, null, null, 2, 0);
        self::assertSame(5, $page1['totalCount']);
        self::assertCount(2, $page1['items']);

        $page2 = $this->subject->getChangelogs(null, null, null, null, 2, 2);
        self::assertSame(5, $page2['totalCount']);
        self::assertCount(2, $page2['items']);

        $page3 = $this->subject->getChangelogs(null, null, null, null, 2, 4);
        self::assertSame(5, $page3['totalCount']);
        self::assertCount(1, $page3['items']);

        $page4 = $this->subject->getChangelogs(null, null, null, null, 2, 6);
        self::assertSame(5, $page4['totalCount']);
        self::assertCount(0, $page4['items']);

        // Distinct items across pages
        $page1Uids = array_column($page1['items'], 'uid');
        $page2Uids = array_column($page2['items'], 'uid');
        self::assertEmpty(array_intersect($page1Uids, $page2Uids));
    }

    #[Test]
    public function getChangelogsAppliesIntentScoringForKeywords(): void
    {
        $this->subject->create(new Changelog('', "# Breaking: #401 - Database Connection removed\n\nDesc", '/core/14.0/breaking-401-db.rst'));
        $this->subject->create(new Changelog('', "# Feature: #402 - Database Connection implement\n\nDesc", '/core/14.0/feature-402-db.rst'));

        // 'removed' keyword triggers isBreakingIntent (+50 for Breaking)
        $results = $this->subject->getChangelogs('Database Connection removed');
        self::assertGreaterThanOrEqual(1, $results['totalCount']);
        self::assertSame('Breaking', $results['items'][0]['change_type']);

        // 'implement' keyword triggers isFeatureIntent (+50 for Feature)
        $resultsFeature = $this->subject->getChangelogs('Database Connection implement');
        self::assertGreaterThanOrEqual(1, $resultsFeature['totalCount']);
        self::assertSame('Feature', $resultsFeature['items'][0]['change_type']);
    }
}
