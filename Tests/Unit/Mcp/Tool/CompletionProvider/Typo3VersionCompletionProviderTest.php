<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Mcp\Tool\CompletionProvider;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\Mcp\Tool\CompletionProvider\Typo3VersionCompletionProvider;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class Typo3VersionCompletionProviderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function getCompletionsReturnsEmptyArrayForEmptyOrNullInput(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $provider = new Typo3VersionCompletionProvider($connectionPool);

        self::assertSame([], $provider->getCompletions(null));
        self::assertSame([], $provider->getCompletions(''));
    }

    #[Test]
    public function getCompletionsWithMajorVersionQueriesMajorColumnAndPrependsMajor(): void
    {
        GeneralUtility::addInstance(DeletedRestriction::class, $this->createMock(DeletedRestriction::class));
        GeneralUtility::addInstance(HiddenRestriction::class, $this->createMock(HiddenRestriction::class));

        $connectionPool = $this->createMock(ConnectionPool::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $restrictions = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $result = $this->createMock(Result::class);

        $restrictions->method('removeAll')->willReturnSelf();
        $restrictions->method('add')->willReturnSelf();

        $queryBuilder->method('getRestrictions')->willReturn($restrictions);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('distinct')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('createNamedParameter')->willReturn(':dcValue1');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $result->method('fetchFirstColumn')->willReturn(['14.0', '14.1']);

        $expressionBuilder->expects($this->once())
            ->method('eq')
            ->with('major_string', ':dcValue1')
            ->willReturn('major_string = :dcValue1');

        $connectionPool->expects($this->once())
            ->method('getQueryBuilderForTable')
            ->with('tx_changelogmcp_changelog')
            ->willReturn($queryBuilder);

        $provider = new Typo3VersionCompletionProvider($connectionPool);
        $completions = $provider->getCompletions('14');

        self::assertSame(['14', '14.0', '14.1'], array_values($completions));
    }

    #[Test]
    public function getCompletionsWithMinorVersionQueriesVersionStringColumn(): void
    {
        GeneralUtility::addInstance(DeletedRestriction::class, $this->createMock(DeletedRestriction::class));
        GeneralUtility::addInstance(HiddenRestriction::class, $this->createMock(HiddenRestriction::class));

        $connectionPool = $this->createMock(ConnectionPool::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $restrictions = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $result = $this->createMock(Result::class);

        $restrictions->method('removeAll')->willReturnSelf();
        $restrictions->method('add')->willReturnSelf();

        $queryBuilder->method('getRestrictions')->willReturn($restrictions);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('distinct')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->method('createNamedParameter')->willReturn(':dcValue1');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $result->method('fetchFirstColumn')->willReturn(['13.4.1', '13.4.2']);

        $expressionBuilder->expects($this->once())
            ->method('eq')
            ->with('version_string', ':dcValue1')
            ->willReturn('version_string = :dcValue1');

        $connectionPool->expects($this->once())
            ->method('getQueryBuilderForTable')
            ->with('tx_changelogmcp_changelog')
            ->willReturn($queryBuilder);

        $provider = new Typo3VersionCompletionProvider($connectionPool);
        $completions = $provider->getCompletions('13.4');

        self::assertSame(['13', '13.4.1', '13.4.2'], array_values($completions));
    }
}
