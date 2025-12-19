<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;
use Tourze\RSSFeedCollectBundle\Service\RssFeedCollectServiceInterface;

/**
 * 批量RSS Feed抓取命令
 * 遍历所有RSS源并执行抓取，提供统计功能
 */
#[AsCommand(
    name: 'rss:collect-feeds',
    description: 'Collect all due RSS feeds and save items to database',
)]
final class FeedCollectAllCommand extends Command
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
            ->setDescription('Collect all due RSS feeds and save items to database')
            ->setHelp('This command fetches all due RSS feeds and saves new items to the database with deduplication.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force collection for all active feeds ignoring intervals'
            )
            ->addOption(
                'stats',
                's',
                InputOption::VALUE_NONE,
                'Show collection statistics only'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('RSS Feed Batch Collection Tool');

        // 显示统计信息
        if ((bool) $input->getOption('stats')) {
            $this->showStatistics($io);

            return Command::SUCCESS;
        }

        $force = (bool) $input->getOption('force');

        try {
            return $this->collectAllDueFeeds($io, $force);
        } catch (\Exception $e) {
            $io->error([
                'Batch collection failed with error:',
                $e->getMessage(),
            ]);

            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), null, 'fg=red');
            }

            return Command::FAILURE;
        }
    }

    /**
     * 显示统计信息
     */
    private function showStatistics(SymfonyStyle $io): void
    {
        $stats = $this->collectService->getCollectStatistics();

        $io->section('RSS Feed Collection Statistics');

        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Feeds', $stats['total_feeds']],
                ['Active Feeds', $stats['active_feeds']],
                ['Error Feeds', $stats['error_feeds']],
                ['Total Items', number_format(is_numeric($stats['total_items']) ? (float) $stats['total_items'] : 0)],
            ]
        );

        // 显示最近有错误的RSS源
        $errorFeeds = array_filter(
            $this->rssFeedRepository->findAll(),
            fn (RssFeed $feed) => 'error' === $feed->getStatus()
        );

        if (count($errorFeeds) > 0) {
            $io->section('Feeds with Errors');

            $rows = [];
            foreach ($errorFeeds as $feed) {
                $rows[] = [
                    $feed->getId(),
                    $feed->getName(),
                    $feed->getLastCollectTime()?->format('Y-m-d H:i:s') ?? 'Never',
                    $feed->getLastError() ?? 'Unknown error',
                ];
            }

            $io->table(
                ['Feed ID', 'Name', 'Last Attempt', 'Error'],
                $rows
            );
        }

        // 显示需要抓取的RSS源
        $activeFeeds = $this->rssFeedRepository->findActiveFeeds();
        $dueFeeds = array_filter(
            $activeFeeds,
            fn (RssFeed $feed) => $this->collectService->shouldCollectFeed($feed)
        );

        if (count($dueFeeds) > 0) {
            $io->section('Feeds Due for Collection');

            $rows = [];
            foreach ($dueFeeds as $feed) {
                $rows[] = [
                    $feed->getId(),
                    $feed->getName(),
                    $feed->getLastCollectTime()?->format('Y-m-d H:i:s') ?? 'Never',
                    $feed->getCollectIntervalMinutes() . ' minutes',
                ];
            }

            $io->table(
                ['Feed ID', 'Name', 'Last Collect', 'Interval'],
                $rows
            );
        } else {
            $io->note('No feeds are currently due for collection.');
        }
    }

    /**
     * 收集所有到期的RSS源
     */
    private function collectAllDueFeeds(SymfonyStyle $io, bool $force): int
    {
        $io->section('Starting RSS Feed Batch Collection');

        if ($force) {
            $io->note('Force mode enabled - ignoring collection intervals');
            $activeFeeds = $this->rssFeedRepository->findActiveFeeds();
            $result = $this->collectService->collectFeeds($activeFeeds, true);
        } else {
            $result = $this->collectService->collectDueFeeds();
        }

        $this->displayCollectionResults($io, $result);

        if ($result['failed'] > 0) {
            $io->warning(sprintf(
                'Batch collection completed with %d failures out of %d total feeds',
                $result['failed'],
                $result['success'] + $result['failed']
            ));

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Batch collection completed successfully! %d feeds processed.',
            $result['success']
        ));

        return Command::SUCCESS;
    }

    /**
     * 显示收集结果详情
     *
     * @param array{success: int, failed: int, details: array<int, array{feed_id: int, feed_name: string, status: string, items_count?: int, error?: string}>} $result
     */
    private function displayCollectionResults(SymfonyStyle $io, array $result): void
    {
        $io->table(
            ['Summary', 'Count'],
            [
                ['Successful', $result['success']],
                ['Failed', $result['failed']],
                ['Total', $result['success'] + $result['failed']],
            ]
        );

        if (count($result['details']) > 0) {
            $rows = [];
            foreach ($result['details'] as $detail) {
                $status = $detail['status'];
                $icon = 'success' === $status ? '✓' : '✗';

                $row = [
                    $detail['feed_id'],
                    $detail['feed_name'],
                    $icon . ' ' . ucfirst($status),
                ];

                if ('success' === $status) {
                    $row[] = ($detail['items_count'] ?? 0) . ' items';
                } else {
                    $row[] = $detail['error'] ?? 'Unknown error';
                }

                $rows[] = $row;
            }

            $io->table(
                ['Feed ID', 'Name', 'Status', 'Result'],
                $rows
            );
        }
    }
}
