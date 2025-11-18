<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;

/**
 * RSS源信息实体
 * 使用TimestampableAware trait，并重新映射列名以保持数据库兼容性
 */
#[ORM\Entity(repositoryClass: RssFeedRepository::class)]
#[ORM\Table(name: 'rss_feeds', options: ['comment' => 'RSS源信息表'])]
#[ORM\UniqueConstraint(name: 'url_unique', columns: ['url'])]
#[ORM\AttributeOverrides(overrides: [
    new ORM\AttributeOverride(name: 'createTime', column: new ORM\Column(name: 'created_at')),
    new ORM\AttributeOverride(name: 'updateTime', column: new ORM\Column(name: 'updated_at')),
])]
class RssFeed
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'RSS源名称'])]
    #[Assert\NotBlank(message: 'RSS源名称不能为空')]
    #[Assert\Length(max: 255, maxMessage: 'RSS源名称不能超过255个字符')]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 500, options: ['comment' => 'RSS源URL地址'])]
    #[Assert\NotBlank(message: 'RSS源URL不能为空')]
    #[Assert\Url(message: '请输入有效的URL地址')]
    #[Assert\Length(max: 500, maxMessage: 'URL长度不能超过500个字符')]
    private string $url;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'RSS源描述信息'])]
    #[Assert\Length(max: 1000, maxMessage: '描述不能超过1000个字符')]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否激活状态'])]
    #[Assert\Type(type: 'bool', message: '激活状态必须为布尔值')]
    private bool $isActive = true;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '分类名称'])]
    #[Assert\Length(max: 100, maxMessage: '分类名称不能超过100个字符')]
    private ?string $category = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '抓取间隔(分钟)', 'default' => 60])]
    #[Assert\Positive(message: '抓取间隔必须为正整数')]
    #[Assert\Range(min: 1, max: 10080, notInRangeMessage: '抓取间隔必须在1分钟到7天之间')]
    private int $collectIntervalMinutes = 60;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后抓取时间'])]
    #[Assert\Valid]
    private ?\DateTimeImmutable $lastCollectTime = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '状态: active/error/disabled', 'default' => 'active'])]
    #[Assert\Choice(choices: ['active', 'error', 'disabled'], message: '状态必须是 active、error 或 disabled')]
    private string $status = 'active';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '最后错误信息'])]
    #[Assert\Length(max: 1000, maxMessage: '错误信息长度不能超过1000字符')]
    private ?string $lastError = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '文章总数(统计字段)', 'default' => 0])]
    #[Assert\PositiveOrZero(message: '文章总数不能为负数')]
    private int $itemsCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getCollectIntervalMinutes(): int
    {
        return $this->collectIntervalMinutes;
    }

    public function setCollectIntervalMinutes(int $collectIntervalMinutes): void
    {
        $this->collectIntervalMinutes = $collectIntervalMinutes;
    }

    public function getLastCollectTime(): ?\DateTimeImmutable
    {
        return $this->lastCollectTime;
    }

    public function setLastCollectTime(?\DateTimeImmutable $lastCollectTime): void
    {
        $this->lastCollectTime = $lastCollectTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): void
    {
        $this->lastError = $lastError;
    }

    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    public function setItemsCount(int $itemsCount): void
    {
        $this->itemsCount = $itemsCount;
    }

    public function incrementItemsCount(): void
    {
        ++$this->itemsCount;
    }

    public function isCollectDue(): bool
    {
        if ('active' !== $this->status) {
            return false;
        }

        if (null === $this->lastCollectTime) {
            return true;
        }

        $nextCollectTime = $this->lastCollectTime->modify("+{$this->collectIntervalMinutes} minutes");

        return new \DateTimeImmutable() >= $nextCollectTime;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
