<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\Test;
use StefanFroemken\ChangelogMcp\Command\PrepareChangelogCommand;
use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use StefanFroemken\ChangelogMcp\Service\Changelog;
use StefanFroemken\ChangelogMcp\Service\ChangelogService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PrepareChangelogCommandTest extends UnitTestCase
{
    #[Test]
    public function executeTruncatesAndCreatesChangelogs(): void
    {
        $repositoryMock = $this->createMock(ChangelogRepository::class);
        $serviceMock = $this->createMock(ChangelogService::class);

        $file1 = '/path/to/feature-1.rst';
        $file2 = '/path/to/invalid.rst';

        $serviceMock->expects($this->once())
            ->method('getAllOriginalTypo3ChangelogFiles')
            ->willReturn([$file1, $file2]);

        $changelog1 = new Changelog('rst content', 'md content', $file1);

        $serviceMock->expects($this->exactly(2))
            ->method('getChangelog')
            ->willReturnMap([
                [$file1, $changelog1],
                [$file2, null],
            ]);

        $repositoryMock->expects($this->once())
            ->method('truncate');

        $repositoryMock->expects($this->once())
            ->method('create')
            ->with($changelog1);

        $command = new PrepareChangelogCommand($repositoryMock, $serviceMock);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('Starting changelog preparation...', $output);
        self::assertStringContainsString('Found 2 changelog files to process.', $output);
        self::assertStringContainsString('Existing changelog entries truncated.', $output);
        self::assertStringContainsString('Could not parse changelog file "/path/to/invalid.rst"', $output);
        self::assertStringContainsString('Successfully stored 1 changelog entries.', $output);
    }

    #[Test]
    public function executeInVerboseModePrintsIndividualFileNames(): void
    {
        $repositoryMock = self::createStub(ChangelogRepository::class);
        $serviceMock = self::createStub(ChangelogService::class);

        $file1 = '/path/to/14.0/feature-999.rst';
        $serviceMock->method('getAllOriginalTypo3ChangelogFiles')->willReturn([$file1]);
        $serviceMock->method('getChangelog')->willReturn(new Changelog('', '', $file1));

        $command = new PrepareChangelogCommand($repositoryMock, $serviceMock);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('Processing: feature-999.rst', $output);
    }
}
