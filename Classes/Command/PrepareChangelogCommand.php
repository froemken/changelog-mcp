<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/changelog-mcp.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ChangelogMcp\Command;

use StefanFroemken\ChangelogMcp\Domain\Repository\ChangelogRepository;
use StefanFroemken\ChangelogMcp\MarkDown\ParserFactory;
use StefanFroemken\ChangelogMcp\Service\Changelog;
use StefanFroemken\ChangelogMcp\Service\ChangelogService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mcp:changelog:prepare',
)]
class PrepareChangelogCommand extends Command
{
    public function __construct(
        protected ChangelogRepository $changelogRepository,
        protected ChangelogService $changelogService,
        protected ParserFactory $parserFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Prepares and stores TYPO3 changelog entries in the database.')
            ->setHelp('This command reads all original TYPO3 changelog RST files, converts them to Markdown, extracts relevant information, and stores them in the database for faster access by the MCP server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->changelogService->getAllOriginalTypo3ChangelogFiles();

        $output->writeln('<info>Starting changelog preparation...</info>');
        $output->writeln(sprintf('Found <comment>%d</comment> changelog files to process.', count($files)));

        // Truncate existing changelog entries before processing new ones
        $this->changelogRepository->truncate();
        $output->writeln('<info>Existing changelog entries truncated.</info>');

        $isVerbose = $output->isVerbose();
        $processedFilesCount = 0;
        $progressBar = null;

        if (!$isVerbose) {
            $progressBar = new ProgressBar($output, count($files));

            // In non-verbose mode, we do not display filenames in the progress bar
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
            $progressBar->start();
        }

        foreach ($files as $absFile) {
            $parser = $this->parserFactory->getParser();

            if ($isVerbose) {
                $output->writeln(sprintf('Processing: <info>%s</info>', basename($absFile)));
            }

            $changelog = $this->changelogService->getChangelog($absFile);
            if (!$changelog instanceof Changelog) {
                $output->writeln(sprintf('Could not parse changelog file "%s"', $absFile));
                $progressBar?->advance(); // Nullsafe operator applied
                continue;
            }

            $renderedContent = $parser->parse($changelog->getContent())->render();
            $this->changelogRepository->create(
                $changelog,
                $renderedContent,
            );
            $processedFilesCount++;
            $progressBar?->advance();
        }

        $progressBar?->finish();
        $output->writeln(PHP_EOL . sprintf('<info>Finished processing. Successfully stored <comment>%d</comment> changelog entries.</info>', $processedFilesCount));

        return Command::SUCCESS;
    }
}
