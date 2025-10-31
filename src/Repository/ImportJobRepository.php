<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RSSFeedCollectBundle\Entity\ImportJob;

/**
 * @extends ServiceEntityRepository<ImportJob>
 *
 * @phpstan-method ImportJob|null find($id, $lockMode = null, $lockVersion = null)
 * @phpstan-method ImportJob|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @phpstan-method ImportJob[]    findAll()
 * @phpstan-method ImportJob[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
#[AsRepository(entityClass: ImportJob::class)]
class ImportJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportJob::class);
    }

    /**
     * 查找所有待处理的任务
     *
     * @return ImportJob[]
     */
    public function findPendingJobs(): array
    {
        /** @var ImportJob[] */
        return $this->createQueryBuilder('ij')
            ->where('ij.status = :status')
            ->setParameter('status', ImportJob::STATUS_PENDING)
            ->orderBy('ij.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 更新任务进度
     *
     * @param string[] $errors
     */
    public function updateProgress(
        ImportJob $importJob,
        int $processedItems,
        int $successfulItems,
        int $failedItems,
        array $errors = [],
    ): void {
        $importJob->setProcessedItems($processedItems);
        $importJob->setSuccessfulItems($successfulItems);
        $importJob->setFailedItems($failedItems);

        if ([] !== $errors) {
            $existingErrors = $importJob->getErrors();
            $importJob->setErrors(array_values(array_merge($existingErrors, $errors)));
        }

        // 如果所有项目都处理完毕，更新状态为完成
        if ($processedItems >= $importJob->getTotalItems()) {
            $importJob->setStatus(ImportJob::STATUS_COMPLETED);
            $importJob->setCompleteTime(new \DateTimeImmutable());
        }

        $this->getEntityManager()->flush();
    }

    /**
     * 清理过期的旧任务
     *
     * @param int $daysOld 删除多少天前的任务
     * @return int 删除的任务数量
     */
    public function cleanupOldJobs(int $daysOld = 30): int
    {
        $cutoffDate = (new \DateTimeImmutable())->modify("-{$daysOld} days");

        /** @var int */
        return $this->createQueryBuilder('ij')
            ->delete()
            ->where('ij.createTime < :cutoffDate')
            ->andWhere('ij.status IN (:completedStatuses)')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('completedStatuses', [ImportJob::STATUS_COMPLETED, ImportJob::STATUS_FAILED])
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 保存导入任务
     */
    public function save(ImportJob $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除导入任务
     */
    public function remove(ImportJob $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找正在处理的任务
     *
     * @return ImportJob[]
     */
    public function findProcessingJobs(): array
    {
        return $this->findByStatus(ImportJob::STATUS_PROCESSING);
    }

    /**
     * 根据状态查找任务
     *
     * @return ImportJob[]
     */
    public function findByStatus(string $status): array
    {
        /** @var ImportJob[] */
        return $this->createQueryBuilder('ij')
            ->where('ij.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ij.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找失败的任务
     *
     * @return ImportJob[]
     */
    public function findFailedJobs(): array
    {
        return $this->findByStatus(ImportJob::STATUS_FAILED);
    }

    /**
     * 查找已完成的任务
     *
     * @return ImportJob[]
     */
    public function findCompletedJobs(): array
    {
        return $this->findByStatus(ImportJob::STATUS_COMPLETED);
    }

    /**
     * 将任务标记为失败
     *
     * @param string[] $errors
     */
    public function markAsFailed(ImportJob $importJob, array $errors): void
    {
        $importJob->setStatus(ImportJob::STATUS_FAILED);
        $importJob->setErrors(array_values($errors));
        $importJob->setCompleteTime(new \DateTimeImmutable());

        $this->getEntityManager()->flush();
    }

    /**
     * 将任务标记为处理中
     */
    public function markAsProcessing(ImportJob $importJob): void
    {
        $importJob->setStatus(ImportJob::STATUS_PROCESSING);

        $this->getEntityManager()->flush();
    }
}
