<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;
use Tourze\RSSFeedCollectBundle\Service\RssFeedCollectServiceInterface;

/**
 * 单个RSS Feed抓取命令
 * 专门处理单个RSS源的抓取任务，职责单一
 */
#[AsCommand(
    name: 'rss:collect-feed',
    description: 'Collect a single RSS feed and save items to database',
)]
final class FeedCollectCommand extends Command
{
    public function __construct(
        private readonly RssFeedCollectServiceInterface $collectService,
        private readonly RssFeedRepository $rssFeedRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Collect a single RSS feed and save items to database')
            ->setHelp('This command fetches a specific RSS feed and saves new items to the database with deduplication.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force collection ignoring the collect interval'
            )
            ->addOption(
                'feed-id',
                null,
                InputOption::VALUE_REQUIRED,
                'RSS feed ID to collect (required)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('RSS Feed Collection Tool');

        $force = (bool) $input->getOption('force');
        $feedId = $input->getOption('feed-id');

        if (null === $feedId) {
            $io->error('Feed ID is required. Use --feed-id option to specify the RSS feed to collect.');

            return Command::INVALID;
        }

        if (!is_numeric($feedId)) {
            $io->error('Feed ID must be a valid integer.');

            return Command::INVALID;
        }

        try {
            return $this->collectSpecificFeed($io, (int) $feedId, $force);
        } catch (\Exception $e) {
            $io->error([
                'Collection failed with error:',
                $e->getMessage(),
            ]);

            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), null, 'fg=red');
            }

            return Command::FAILURE;
        }
    }

    /**
     * 收集指定的RSS源
     */
    private function collectSpecificFeed(SymfonyStyle $io, int $feedId, bool $force): int
    {
        $rssFeed = $this->rssFeedRepository->find($feedId);

        if (null === $rssFeed) {
            $io->error("RSS Feed with ID {$feedId} not found.");

            return Command::INVALID;
        }

        $io->section("Collecting RSS Feed: {$rssFeed->getName()}");

        $io->table(
            ['Property', 'Value'],
            [
                ['ID', $rssFeed->getId()],
                ['Name', $rssFeed->getName()],
                ['URL', $rssFeed->getUrl()],
                ['Status', $rssFeed->getStatus()],
                ['Interval', $rssFeed->getCollectIntervalMinutes() . ' minutes'],
                ['Last Collect', $rssFeed->getLastCollectTime()?->format('Y-m-d H:i:s') ?? 'Never'],
                ['Items Count', $rssFeed->getItemsCount()],
            ]
        );

        if (!$force && !$this->collectService->shouldCollectFeed($rssFeed)) {
            $io->note('This feed is not due for collection yet. Use --force to override.');

            return Command::SUCCESS;
        }

        $result = $force
            ? $this->collectService->forceCollectFeed($rssFeed)
            : $this->collectService->collectFeed($rssFeed);

        if (!$result['success']) {
            $io->error([
                'Collection failed:',
                $result['error'] ?? 'Unknown error',
            ]);

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Collection completed successfully! %d new items collected.',
            $result['items_count']
        ));

        return Command::SUCCESS;
    }
}
