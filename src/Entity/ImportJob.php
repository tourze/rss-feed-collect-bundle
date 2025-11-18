<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\RSSFeedCollectBundle\Repository\ImportJobRepository;

#[ORM\Entity(repositoryClass: ImportJobRepository::class)]
#[ORM\Table(name: 'import_jobs', options: ['comment' => '导入任务表'])]
class ImportJob
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '任务状态'])]
    #[Assert\NotBlank(message: '任务状态不能为空')]
    #[Assert\Choice(choices: [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_FAILED])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '文件名称'])]
    #[Assert\NotBlank(message: '文件名称不能为空')]
    #[Assert\Length(max: 255, maxMessage: '文件名称不能超过255个字符')]
    private string $fileName;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '总项目数'])]
    #[Assert\PositiveOrZero(message: '总项目数不能为负数')]
    private int $totalItems = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '已处理项目数'])]
    #[Assert\PositiveOrZero(message: '已处理项目数不能为负数')]
    private int $processedItems = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '成功项目数'])]
    #[Assert\PositiveOrZero(message: '成功项目数不能为负数')]
    private int $successfulItems = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '失败项目数'])]
    #[Assert\PositiveOrZero(message: '失败项目数不能为负数')]
    private int $failedItems = 0;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '错误信息JSON'])]
    #[Assert\All(constraints: [
        new Assert\Type(type: 'string'),
    ])]
    private array $errors = [];

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createTime;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '完成时间'])]
    #[Assert\DateTime(message: '完成时间必须是有效的日期时间格式')]
    private ?\DateTimeImmutable $completeTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
    }

    public function getProcessedItems(): int
    {
        return $this->processedItems;
    }

    public function setProcessedItems(int $processedItems): void
    {
        $this->processedItems = $processedItems;
    }

    public function getSuccessfulItems(): int
    {
        return $this->successfulItems;
    }

    public function setSuccessfulItems(int $successfulItems): void
    {
        $this->successfulItems = $successfulItems;
    }

    public function getFailedItems(): int
    {
        return $this->failedItems;
    }

    public function setFailedItems(int $failedItems): void
    {
        $this->failedItems = $failedItems;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return array_values($this->errors);
    }

    /**
     * @param string[] $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getCompleteTime(): ?\DateTimeImmutable
    {
        return $this->completeTime;
    }

    public function setCompleteTime(?\DateTimeImmutable $completeTime): void
    {
        $this->completeTime = $completeTime;
    }

    public function getProgressPercentage(): float
    {
        if (0 === $this->totalItems) {
            return 0.0;
        }

        return ($this->processedItems / $this->totalItems) * 100;
    }

    public function __toString(): string
    {
        return $this->fileName;
    }
}
