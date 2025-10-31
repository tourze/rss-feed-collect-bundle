<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\RSSFeedCollectBundle\Entity\ImportJob;

/**
 * ImportJob 测试数据装配器
 */
class ImportJobFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 创建一个待处理的导入任务
        $pendingJob = new ImportJob();
        $pendingJob->setFileName('feeds-pending.opml');
        $pendingJob->setStatus(ImportJob::STATUS_PENDING);
        $pendingJob->setTotalItems(10);
        $pendingJob->setProcessedItems(0);
        $pendingJob->setSuccessfulItems(0);
        $pendingJob->setFailedItems(0);
        $pendingJob->setErrors([]);
        $pendingJob->setCreateTime(new \DateTimeImmutable('-2 hours'));
        $manager->persist($pendingJob);

        // 创建一个正在处理的导入任务
        $processingJob = new ImportJob();
        $processingJob->setFileName('feeds-processing.opml');
        $processingJob->setStatus(ImportJob::STATUS_PROCESSING);
        $processingJob->setTotalItems(20);
        $processingJob->setProcessedItems(8);
        $processingJob->setSuccessfulItems(6);
        $processingJob->setFailedItems(2);
        $processingJob->setErrors(['Invalid URL format: http://example.invalid', 'Connection timeout: https://slow.feed.com']);
        $processingJob->setCreateTime(new \DateTimeImmutable('-1 hour'));
        $manager->persist($processingJob);

        // 创建一个已完成的导入任务
        $completedJob = new ImportJob();
        $completedJob->setFileName('feeds-completed.opml');
        $completedJob->setStatus(ImportJob::STATUS_COMPLETED);
        $completedJob->setTotalItems(15);
        $completedJob->setProcessedItems(15);
        $completedJob->setSuccessfulItems(14);
        $completedJob->setFailedItems(1);
        $completedJob->setErrors(['Invalid RSS format: https://broken.feed.com/rss']);
        $completedJob->setCreateTime(new \DateTimeImmutable('-3 hours'));
        $completedJob->setCompleteTime(new \DateTimeImmutable('-2 hours 30 minutes'));
        $manager->persist($completedJob);

        // 创建一个失败的导入任务
        $failedJob = new ImportJob();
        $failedJob->setFileName('feeds-failed.opml');
        $failedJob->setStatus(ImportJob::STATUS_FAILED);
        $failedJob->setTotalItems(5);
        $failedJob->setProcessedItems(3);
        $failedJob->setSuccessfulItems(1);
        $failedJob->setFailedItems(2);
        $failedJob->setErrors([
            'Connection refused: https://down.feed.com/rss',
            'SSL certificate error: https://insecure.feed.com/feed',
        ]);
        $failedJob->setCreateTime(new \DateTimeImmutable('-4 hours'));
        $failedJob->setCompleteTime(new \DateTimeImmutable('-3 hours 45 minutes'));
        $manager->persist($failedJob);

        $manager->flush();
    }
}
