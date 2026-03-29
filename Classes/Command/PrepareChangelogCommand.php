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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->changelogService->getAllOriginalTypo3ChangelogFiles();
        $parser = $this->parserFactory->getParser();

        foreach ($files as $absFile) {
            $changelog = $this->changelogService->getChangelog($absFile);
            if (!$changelog instanceof Changelog) {
                $output->writeln(sprintf('Could not parse changelog file "%s"', $absFile));
                continue;
            }

            $this->changelogRepository->create(
                $changelog,
                $parser->parse($changelog->getContent())->render(),
            );
        }

        return Command::SUCCESS;
    }
}
