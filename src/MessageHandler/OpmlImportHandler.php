<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Tourze\RSSFeedCollectBundle\Entity\ImportJob;
use Tourze\RSSFeedCollectBundle\Message\OpmlImportMessage;
use Tourze\RSSFeedCollectBundle\Repository\ImportJobRepository;
use Tourze\RSSFeedCollectBundle\Service\OpmlService;
use Tourze\RSSFeedCollectBundle\Service\RssFeedService;

/**
 * OPML导入异步处理器
 * 处理OPML文件的异步导入任务，包含进度跟踪和错误处理
 */
#[AsMessageHandler]
#[WithMonologChannel(channel: 'rss_feed_collect')]
final class OpmlImportHandler
{
    public function __construct(
        private readonly OpmlService $opmlService,
        private readonly RssFeedService $rssFeedService,
        private readonly ImportJobRepository $importJobRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 处理OPML导入消息
     */
    public function __invoke(OpmlImportMessage $message): void
    {
        $importJob = $this->importJobRepository->find($message->importJobId);
        if (null === $importJob) {
            $this->logger->error('Import job not found', ['jobId' => $message->importJobId]);

            return;
        }

        try {
            $this->processImport($message, $importJob);
        } catch (\Throwable $e) {
            $this->handleImportFailure($importJob, $e);
        }
    }

    /**
     * 处理导入逻辑
     */
    private function processImport(OpmlImportMessage $message, ImportJob $importJob): void
    {
        // 标记任务开始处理
        $this->updateJobStatus($importJob, ImportJob::STATUS_PROCESSING);

        // 读取OPML文件内容
        $opmlContent = $this->readOpmlFile($message->filePath);

        // 解析OPML文件
        $opmlData = $this->opmlService->parseOpmlFile($opmlContent);

        // 更新任务总数
        $totalItems = count($opmlData['feeds']);
        $importJob->setTotalItems($totalItems);
        $this->saveJob($importJob);

        if (0 === $totalItems) {
            $this->completeJobWithoutItems($importJob);

            return;
        }

        // 批量处理feeds
        $this->processFeedsInBatches($opmlData['feeds'], $importJob);

        // 完成任务
        $this->completeJob($importJob);
    }

    /**
     * 更新任务状态
     */
    private function updateJobStatus(ImportJob $importJob, string $status): void
    {
        $importJob->setStatus($status);
        $this->saveJob($importJob);

        $this->logger->info('Import job status updated', [
            'jobId' => $importJob->getId(),
            'status' => $status,
        ]);
    }

    /**
     * 保存任务
     */
    private function saveJob(ImportJob $importJob): void
    {
        try {
            $this->importJobRepository->save($importJob, true);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to save import job', [
                'jobId' => $importJob->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 读取OPML文件内容
     */
    private function readOpmlFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("OPML file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("OPML file is not readable: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new \InvalidArgumentException("Failed to read OPML file: {$filePath}");
        }

        return $content;
    }

    /**
     * 完成没有项目的任务
     */
    private function completeJobWithoutItems(ImportJob $importJob): void
    {
        $importJob->setStatus(ImportJob::STATUS_COMPLETED);
        $importJob->setCompleteTime(new \DateTimeImmutable());

        $this->saveJob($importJob);

        $this->logger->info('Import job completed with no items', [
            'jobId' => $importJob->getId(),
        ]);
    }

    /**
     * 分批处理feeds
     *
     * @param array<array{name: string, url: string, description?: string, category?: string}> $feeds
     */
    private function processFeedsInBatches(array $feeds, ImportJob $importJob): void
    {
        $batchSize = 10;
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach (array_chunk($feeds, $batchSize) as $batch) {
            foreach ($batch as $feedData) {
                try {
                    $this->rssFeedService->createFeed($feedData);
                    ++$successful;
                } catch (\Throwable $e) {
                    ++$failed;
                    $errors[] = sprintf(
                        'Failed to create feed "%s" (%s): %s',
                        $feedData['name'],
                        $feedData['url'],
                        $e->getMessage()
                    );

                    $this->logger->warning('Failed to create RSS feed during import', [
                        'feedName' => $feedData['name'],
                        'feedUrl' => $feedData['url'],
                        'error' => $e->getMessage(),
                        'importJobId' => $importJob->getId(),
                    ]);
                }
                ++$processed;
            }

            // 更新进度
            $this->updateJobProgress($importJob, $processed, $successful, $failed, $errors);
        }
    }

    /**
     * 更新任务进度
     *
     * @param array<string> $errors
     */
    private function updateJobProgress(ImportJob $importJob, int $processed, int $successful, int $failed, array $errors): void
    {
        $importJob->setProcessedItems($processed);
        $importJob->setSuccessfulItems($successful);
        $importJob->setFailedItems($failed);
        $importJob->setErrors(array_values($errors));

        $this->saveJob($importJob);

        $this->logger->info('Import job progress updated', [
            'jobId' => $importJob->getId(),
            'processed' => $processed,
            'successful' => $successful,
            'failed' => $failed,
            'progress' => $importJob->getProgressPercentage(),
        ]);
    }

    /**
     * 完成任务
     */
    private function completeJob(ImportJob $importJob): void
    {
        $importJob->setStatus(ImportJob::STATUS_COMPLETED);
        $importJob->setCompleteTime(new \DateTimeImmutable());

        $this->saveJob($importJob);

        $this->logger->info('Import job completed successfully', [
            'jobId' => $importJob->getId(),
            'totalItems' => $importJob->getTotalItems(),
            'successfulItems' => $importJob->getSuccessfulItems(),
            'failedItems' => $importJob->getFailedItems(),
        ]);
    }

    /**
     * 处理导入失败
     */
    private function handleImportFailure(ImportJob $importJob, \Throwable $exception): void
    {
        $errorMessage = sprintf(
            'Import failed with exception: %s in %s:%d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        $importJob->setStatus(ImportJob::STATUS_FAILED);
        $importJob->setErrors([$errorMessage]);
        $importJob->setCompleteTime(new \DateTimeImmutable());

        $this->saveJob($importJob);

        $this->logger->error('Import job failed', [
            'jobId' => $importJob->getId(),
            'error' => $errorMessage,
            'exception' => $exception,
        ]);
    }
}
