<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RSSFeedCollectBundle\Entity\ImportJob;
use Tourze\RSSFeedCollectBundle\Repository\ImportJobRepository;

/**
 * @internal
 */
#[CoversClass(ImportJobRepository::class)]
#[RunTestsInSeparateProcesses]
class ImportJobRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $job = new ImportJob();
        $job->setFileName('test_' . uniqid() . '.opml');
        $job->setStatus(ImportJob::STATUS_PENDING);
        $job->setTotalItems(100);
        $job->setProcessedItems(0);
        $job->setSuccessfulItems(0);
        $job->setFailedItems(0);
        $job->setErrors([]);
        $job->setCreateTime(new \DateTimeImmutable());

        return $job;
    }

    protected function getRepository(): ImportJobRepository
    {
        return self::getService(ImportJobRepository::class);
    }

    /**
     * @return array<int, array{0: string, 1: array<string>, 2: array<string, string>, 3: string}>
     */
    public static function findOneBySortOrderProvider(): array
    {
        return [
            // 方法名, 字段名, 排序方向, 期望值
            ['findOneBy', ['status'], ['status' => 'ASC'], ImportJob::STATUS_COMPLETED],
        ];
    }

    protected function onSetUp(): void
    {
        // 测试数据准备在这里完成
    }

    public function testFindPendingJobs(): void
    {
        // 手动清理 ImportJob 实体，避免 DataFixtures 影响
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportJob::class)->execute();
        self::getEntityManager()->flush();

        // 创建测试数据
        $pendingJob1 = $this->createImportJob('test1.opml', ImportJob::STATUS_PENDING);
        $pendingJob2 = $this->createImportJob('test2.opml', ImportJob::STATUS_PENDING);
        $completedJob = $this->createImportJob('test3.opml', ImportJob::STATUS_COMPLETED);

        $this->persistAndFlush($pendingJob1);
        $this->persistAndFlush($pendingJob2);
        $this->persistAndFlush($completedJob);

        $result = $this->getRepository()->findPendingJobs();

        $this->assertCount(2, $result);
        $this->assertEquals(ImportJob::STATUS_PENDING, $result[0]->getStatus());
        $this->assertEquals(ImportJob::STATUS_PENDING, $result[1]->getStatus());
    }

    private function createImportJob(string $fileName, string $status): ImportJob
    {
        $importJob = new ImportJob();
        $importJob->setFileName($fileName);
        $importJob->setStatus($status);
        $importJob->setCreateTime(new \DateTimeImmutable());

        return $importJob;
    }

    public function testFindByStatus(): void
    {
        // 手动清理 ImportJob 实体，避免 DataFixtures 影响
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportJob::class)->execute();
        self::getEntityManager()->flush();

        // 创建测试数据
        $pendingJob = $this->createImportJob('test1.opml', ImportJob::STATUS_PENDING);
        $processingJob = $this->createImportJob('test2.opml', ImportJob::STATUS_PROCESSING);
        $completedJob = $this->createImportJob('test3.opml', ImportJob::STATUS_COMPLETED);

        $this->persistAndFlush($pendingJob);
        $this->persistAndFlush($processingJob);
        $this->persistAndFlush($completedJob);

        $pendingJobs = $this->getRepository()->findByStatus(ImportJob::STATUS_PENDING);
        $processingJobs = $this->getRepository()->findByStatus(ImportJob::STATUS_PROCESSING);
        $completedJobs = $this->getRepository()->findByStatus(ImportJob::STATUS_COMPLETED);

        $this->assertCount(1, $pendingJobs);
        $this->assertCount(1, $processingJobs);
        $this->assertCount(1, $completedJobs);

        $this->assertEquals(ImportJob::STATUS_PENDING, $pendingJobs[0]->getStatus());
        $this->assertEquals(ImportJob::STATUS_PROCESSING, $processingJobs[0]->getStatus());
        $this->assertEquals(ImportJob::STATUS_COMPLETED, $completedJobs[0]->getStatus());
    }

    public function testUpdateProgress(): void
    {
        $importJob = $this->createImportJob('test.opml', ImportJob::STATUS_PROCESSING);
        $importJob->setTotalItems(10);

        $this->persistAndFlush($importJob);

        $errors = ['Error processing item 3'];
        $this->getRepository()->updateProgress($importJob, 5, 4, 1, $errors);

        $this->assertEquals(5, $importJob->getProcessedItems());
        $this->assertEquals(4, $importJob->getSuccessfulItems());
        $this->assertEquals(1, $importJob->getFailedItems());
        $this->assertEquals($errors, $importJob->getErrors());
        $this->assertEquals(ImportJob::STATUS_PROCESSING, $importJob->getStatus());
    }

    public function testUpdateProgressCompletesJob(): void
    {
        $importJob = $this->createImportJob('test.opml', ImportJob::STATUS_PROCESSING);
        $importJob->setTotalItems(5);

        $this->persistAndFlush($importJob);

        $this->getRepository()->updateProgress($importJob, 5, 4, 1, []);

        $this->assertEquals(5, $importJob->getProcessedItems());
        $this->assertEquals(ImportJob::STATUS_COMPLETED, $importJob->getStatus());
        $this->assertNotNull($importJob->getCompleteTime());
    }

    public function testUpdateProgressMergesErrors(): void
    {
        $importJob = $this->createImportJob('test.opml', ImportJob::STATUS_PROCESSING);
        $importJob->setTotalItems(10);
        $importJob->setErrors(['Initial error']);

        $this->persistAndFlush($importJob);

        $newErrors = ['New error 1', 'New error 2'];
        $this->getRepository()->updateProgress($importJob, 3, 2, 1, $newErrors);

        $expectedErrors = ['Initial error', 'New error 1', 'New error 2'];
        $this->assertEquals($expectedErrors, $importJob->getErrors());
    }

    public function testCleanupOldJobs(): void
    {
        // 手动清理 ImportJob 实体，避免 DataFixtures 影响
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportJob::class)->execute();
        self::getEntityManager()->flush();

        $now = new \DateTimeImmutable();

        // 创建旧的已完成任务（应该被删除）
        $oldCompletedJob = $this->createImportJob('old_completed.opml', ImportJob::STATUS_COMPLETED);
        $oldCompletedJob->setCreateTime($now->modify('-45 days'));

        // 创建旧的失败任务（应该被删除）
        $oldFailedJob = $this->createImportJob('old_failed.opml', ImportJob::STATUS_FAILED);
        $oldFailedJob->setCreateTime($now->modify('-35 days'));

        // 创建旧的待处理任务（不应该被删除）
        $oldPendingJob = $this->createImportJob('old_pending.opml', ImportJob::STATUS_PENDING);
        $oldPendingJob->setCreateTime($now->modify('-40 days'));

        // 创建新的已完成任务（不应该被删除）
        $newCompletedJob = $this->createImportJob('new_completed.opml', ImportJob::STATUS_COMPLETED);
        $newCompletedJob->setCreateTime($now->modify('-10 days'));

        $this->persistAndFlush($oldCompletedJob);
        $this->persistAndFlush($oldFailedJob);
        $this->persistAndFlush($oldPendingJob);
        $this->persistAndFlush($newCompletedJob);

        $deletedCount = $this->getRepository()->cleanupOldJobs(30);

        $this->assertEquals(2, $deletedCount);

        $remainingJobs = $this->getRepository()->findAll();
        $this->assertCount(2, $remainingJobs);

        $remainingFileNames = array_map(fn ($job) => $job->getFileName(), $remainingJobs);
        $this->assertContains('old_pending.opml', $remainingFileNames);
        $this->assertContains('new_completed.opml', $remainingFileNames);
    }

    public function testFindProcessingJobs(): void
    {
        // 手动清理 ImportJob 实体，避免 DataFixtures 影响
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportJob::class)->execute();
        self::getEntityManager()->flush();

        $processingJob1 = $this->createImportJob('processing1.opml', ImportJob::STATUS_PROCESSING);
        $processingJob2 = $this->createImportJob('processing2.opml', ImportJob::STATUS_PROCESSING);
        $pendingJob = $this->createImportJob('pending.opml', ImportJob::STATUS_PENDING);

        $this->persistAndFlush($processingJob1);
        $this->persistAndFlush($processingJob2);
        $this->persistAndFlush($pendingJob);

        $result = $this->getRepository()->findProcessingJobs();

        $this->assertCount(2, $result);
        foreach ($result as $job) {
            $this->assertEquals(ImportJob::STATUS_PROCESSING, $job->getStatus());
        }
    }

    public function testFindFailedJobs(): void
    {
        // 手动清理 ImportJob 实体，避免 DataFixtures 影响
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportJob::class)->execute();
        self::getEntityManager()->flush();

        $failedJob1 = $this->createImportJob('failed1.opml', ImportJob::STATUS_FAILED);
        $failedJob2 = $this->createImportJob('failed2.opml', ImportJob::STATUS_FAILED);
        $completedJob = $this->createImportJob('completed.opml', ImportJob::STATUS_COMPLETED);

        $this->persistAndFlush($failedJob1);
        $this->persistAndFlush($failedJob2);
        $this->persistAndFlush($completedJob);

        $result = $this->getRepository()->findFailedJobs();

        $this->assertCount(2, $result);
        foreach ($result as $job) {
            $this->assertEquals(ImportJob::STATUS_FAILED, $job->getStatus());
        }
    }

    public function testFindCompletedJobs(): void
    {
        // 手动清理 ImportJob 实体，避免 DataFixtures 影响
        self::getEntityManager()->createQuery('DELETE FROM ' . ImportJob::class)->execute();
        self::getEntityManager()->flush();

        $completedJob1 = $this->createImportJob('completed1.opml', ImportJob::STATUS_COMPLETED);
        $completedJob2 = $this->createImportJob('completed2.opml', ImportJob::STATUS_COMPLETED);
        $pendingJob = $this->createImportJob('pending.opml', ImportJob::STATUS_PENDING);

        $this->persistAndFlush($completedJob1);
        $this->persistAndFlush($completedJob2);
        $this->persistAndFlush($pendingJob);

        $result = $this->getRepository()->findCompletedJobs();

        $this->assertCount(2, $result);
        foreach ($result as $job) {
            $this->assertEquals(ImportJob::STATUS_COMPLETED, $job->getStatus());
        }
    }

    public function testMarkAsFailed(): void
    {
        $importJob = $this->createImportJob('test.opml', ImportJob::STATUS_PROCESSING);

        $this->persistAndFlush($importJob);

        $errors = ['Fatal error occurred', 'Unable to process file'];
        $this->getRepository()->markAsFailed($importJob, $errors);

        $this->assertEquals(ImportJob::STATUS_FAILED, $importJob->getStatus());
        $this->assertEquals($errors, $importJob->getErrors());
        $this->assertNotNull($importJob->getCompleteTime());
    }

    public function testMarkAsProcessing(): void
    {
        $importJob = $this->createImportJob('test.opml', ImportJob::STATUS_PENDING);

        $this->persistAndFlush($importJob);

        $this->getRepository()->markAsProcessing($importJob);

        $this->assertEquals(ImportJob::STATUS_PROCESSING, $importJob->getStatus());
    }
}
