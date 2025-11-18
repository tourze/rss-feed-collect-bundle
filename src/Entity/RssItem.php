<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RSSFeedCollectBundle\Repository\RssItemRepository;

/**
 * RSS文章实体类
 * 存储从RSS源抓取的文章内容，基于链接去重
 */
#[ORM\Entity(repositoryClass: RssItemRepository::class)]
#[ORM\Table(name: 'rss_items', options: ['comment' => 'RSS文章内容表'])]
#[ORM\UniqueConstraint(name: 'link_unique', columns: ['link'])]
#[ORM\Index(columns: ['rss_feed_id', 'publish_time'], name: 'rss_items_idx_feed_publish_time')]
class RssItem
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 500, options: ['comment' => '文章标题'])]
    #[Assert\NotBlank(message: '文章标题不能为空')]
    #[Assert\Length(max: 500, maxMessage: '文章标题不能超过500个字符')]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 1000, options: ['comment' => '文章链接地址(去重依据)'])]
    #[Assert\NotBlank(message: '文章链接不能为空')]
    #[Assert\Url(message: '请输入有效的URL地址')]
    #[Assert\Length(max: 1000, maxMessage: '链接长度不能超过1000个字符')]
    private string $link;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '文章摘要'])]
    #[Assert\Length(max: 5000, maxMessage: '文章摘要长度不能超过5000字符')]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '文章内容'])]
    #[Assert\Length(max: 50000, maxMessage: '文章内容长度不能超过50000字符')]
    private ?string $content = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'RSS GUID标识符'])]
    #[Assert\NotBlank(message: 'RSS GUID不能为空')]
    #[Assert\Length(max: 255, maxMessage: 'GUID长度不能超过255个字符')]
    private string $guid;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '文章发布时间'])]
    #[Assert\Valid]
    private ?\DateTimeImmutable $publishTime = null;

    #[ORM\ManyToOne(targetEntity: RssFeed::class)]
    #[ORM\JoinColumn(name: 'rss_feed_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: '所属RSS源不能为空')]
    private RssFeed $rssFeed;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    public function getPublishTime(): ?\DateTimeImmutable
    {
        return $this->publishTime;
    }

    public function setPublishTime(?\DateTimeImmutable $publishTime): void
    {
        $this->publishTime = $publishTime;
    }

    public function getRssFeed(): RssFeed
    {
        return $this->rssFeed;
    }

    public function setRssFeed(RssFeed $rssFeed): void
    {
        $this->rssFeed = $rssFeed;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
