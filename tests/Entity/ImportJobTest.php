<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RSSFeedCollectBundle\Entity\ImportJob;

/**
 * @internal
 */
#[CoversClass(ImportJob::class)]
class ImportJobTest extends AbstractEntityTestCase
{
    protected function createEntity(): ImportJob
    {
        $importJob = new ImportJob();
        $importJob->setFileName('test-' . uniqid() . '.opml');
        $importJob->setStatus(ImportJob::STATUS_PENDING);
        $importJob->setTotalItems(100);
        $importJob->setProcessedItems(0);
        $importJob->setSuccessfulItems(0);
        $importJob->setFailedItems(0);
        $importJob->setErrors([]);
        $importJob->setCreateTime(new \DateTimeImmutable());

        return $importJob;
    }

    /**
     * @return array<int, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            ['fileName', 'test.opml'],
            ['status', ImportJob::STATUS_PROCESSING],
            ['totalItems', 100],
            ['processedItems', 50],
            ['successfulItems', 45],
            ['failedItems', 5],
            ['errors', ['Error 1', 'Error 2']],
            ['createTime', new \DateTimeImmutable()],
            ['completeTime', new \DateTimeImmutable()],
        ];
    }

    public function testCreateImportJob(): void
    {
        $importJob = new ImportJob();

        $this->assertNull($importJob->getId());
        $this->assertSame(ImportJob::STATUS_PENDING, $importJob->getStatus());
        $this->assertSame(0, $importJob->getTotalItems());
        $this->assertSame(0, $importJob->getProcessedItems());
        $this->assertSame(0, $importJob->getSuccessfulItems());
        $this->assertSame(0, $importJob->getFailedItems());
        $this->assertSame([], $importJob->getErrors());
        $this->assertNull($importJob->getCompleteTime());
    }

    public function testSetAndGetStatus(): void
    {
        $importJob = new ImportJob();

        $importJob->setStatus(ImportJob::STATUS_PROCESSING);

        $this->assertSame(ImportJob::STATUS_PROCESSING, $importJob->getStatus());
    }

    public function testSetAndGetFileName(): void
    {
        $importJob = new ImportJob();
        $fileName = 'test.opml';

        $importJob->setFileName($fileName);

        $this->assertSame($fileName, $importJob->getFileName());
        $this->assertSame($fileName, $importJob->__toString());
    }

    public function testSetAndGetTotalItems(): void
    {
        $importJob = new ImportJob();
        $totalItems = 100;

        $importJob->setTotalItems($totalItems);

        $this->assertSame($totalItems, $importJob->getTotalItems());
    }

    public function testSetAndGetProcessedItems(): void
    {
        $importJob = new ImportJob();
        $processedItems = 50;

        $importJob->setProcessedItems($processedItems);

        $this->assertSame($processedItems, $importJob->getProcessedItems());
    }

    public function testSetAndGetSuccessfulItems(): void
    {
        $importJob = new ImportJob();
        $successfulItems = 45;

        $importJob->setSuccessfulItems($successfulItems);

        $this->assertSame($successfulItems, $importJob->getSuccessfulItems());
    }

    public function testSetAndGetFailedItems(): void
    {
        $importJob = new ImportJob();
        $failedItems = 5;

        $importJob->setFailedItems($failedItems);

        $this->assertSame($failedItems, $importJob->getFailedItems());
    }

    public function testSetAndGetErrors(): void
    {
        $importJob = new ImportJob();
        $errors = ['Error 1', 'Error 2'];

        $importJob->setErrors($errors);

        $this->assertSame($errors, $importJob->getErrors());
    }

    public function testSetAndGetCreateTime(): void
    {
        $importJob = new ImportJob();
        $createTime = new \DateTimeImmutable('2025-01-01 12:00:00');

        $importJob->setCreateTime($createTime);

        $this->assertSame($createTime, $importJob->getCreateTime());
    }

    public function testSetAndGetCompleteTime(): void
    {
        $importJob = new ImportJob();
        $completeTime = new \DateTimeImmutable('2025-01-01 12:30:00');

        $importJob->setCompleteTime($completeTime);

        $this->assertSame($completeTime, $importJob->getCompleteTime());
    }

    public function testGetProgressPercentageWhenTotalIsZero(): void
    {
        $importJob = new ImportJob();

        $this->assertSame(0.0, $importJob->getProgressPercentage());
    }

    public function testGetProgressPercentageWithValues(): void
    {
        $importJob = new ImportJob();
        $importJob->setTotalItems(100);
        $importJob->setProcessedItems(25);

        $this->assertSame(25.0, $importJob->getProgressPercentage());
    }

    public function testGetProgressPercentageComplete(): void
    {
        $importJob = new ImportJob();
        $importJob->setTotalItems(50);
        $importJob->setProcessedItems(50);

        $this->assertSame(100.0, $importJob->getProgressPercentage());
    }

    public function testSetAllProperties(): void
    {
        $importJob = new ImportJob();
        $fileName = 'test.opml';
        $status = ImportJob::STATUS_COMPLETED;
        $totalItems = 100;
        $processedItems = 95;
        $successfulItems = 90;
        $failedItems = 5;
        $errors = ['Error 1'];
        $createTime = new \DateTimeImmutable('2025-01-01 12:00:00');
        $completeTime = new \DateTimeImmutable('2025-01-01 12:30:00');

        // 使用分离的语句设置所有属性（符合 void 返回值的 setter）
        $importJob->setStatus($status);
        $importJob->setFileName($fileName);
        $importJob->setTotalItems($totalItems);
        $importJob->setProcessedItems($processedItems);
        $importJob->setSuccessfulItems($successfulItems);
        $importJob->setFailedItems($failedItems);
        $importJob->setErrors($errors);
        $importJob->setCreateTime($createTime);
        $importJob->setCompleteTime($completeTime);

        // 验证所有属性都被正确设置
        $this->assertSame($status, $importJob->getStatus());
        $this->assertSame($fileName, $importJob->getFileName());
        $this->assertSame($totalItems, $importJob->getTotalItems());
        $this->assertSame($processedItems, $importJob->getProcessedItems());
        $this->assertSame($successfulItems, $importJob->getSuccessfulItems());
        $this->assertSame($failedItems, $importJob->getFailedItems());
        $this->assertSame($errors, $importJob->getErrors());
        $this->assertSame($createTime, $importJob->getCreateTime());
        $this->assertSame($completeTime, $importJob->getCompleteTime());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('pending', ImportJob::STATUS_PENDING);
        $this->assertSame('processing', ImportJob::STATUS_PROCESSING);
        $this->assertSame('completed', ImportJob::STATUS_COMPLETED);
        $this->assertSame('failed', ImportJob::STATUS_FAILED);
    }
}
