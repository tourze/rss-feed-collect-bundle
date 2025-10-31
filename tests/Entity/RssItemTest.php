<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;

/**
 * RssItem Entity 单元测试
 * @internal
 */
#[CoversClass(RssItem::class)]
class RssItemTest extends AbstractEntityTestCase
{
    protected function createEntity(): RssItem
    {
        $rssFeed = new RssFeed();
        $rssFeed->setName('Test Feed');
        $rssFeed->setUrl('https://example.com/feed.xml');
        $rssFeed->setCreateTime(new \DateTimeImmutable());
        $rssFeed->setUpdateTime(new \DateTimeImmutable());

        $rssItem = new RssItem();
        $rssItem->setTitle('Test Item - ' . uniqid());
        $rssItem->setLink('https://example.com/article-' . uniqid());
        $rssItem->setDescription('Test article description');
        $rssItem->setGuid('guid-' . uniqid());
        $rssItem->setRssFeed($rssFeed);
        $rssItem->setPublishTime(new \DateTimeImmutable());

        return $rssItem;
    }

    /**
     * @return array<int, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        $rssFeed = new RssFeed();
        $rssFeed->setName('Test Feed');
        $rssFeed->setUrl('https://example.com/feed.xml');

        return [
            ['title', 'Test Article Title'],
            ['link', 'https://example.com/article'],
            ['description', 'Test description'],
            ['content', '<p>Test content</p>'],
            ['guid', 'test-guid'],
            ['publishTime', new \DateTimeImmutable()],
            ['rssFeed', $rssFeed],
            ['createTime', new \DateTimeImmutable()],
            ['updateTime', new \DateTimeImmutable()],
        ];
    }

    public function testRssItemCreation(): void
    {
        $rssItem = new RssItem();

        $this->assertNull($rssItem->getId());
        // TimestampableAware trait timestamps are set by event listeners, so they may be null in tests
        $createTime = $rssItem->getCreateTime();
        $updateTime = $rssItem->getUpdateTime();
        if (null !== $createTime) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $createTime);
        }
        if (null !== $updateTime) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);
        }
    }

    public function testTitleSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $title = 'Test RSS Article Title';

        $rssItem->setTitle($title);

        $this->assertSame($title, $rssItem->getTitle());
        $this->assertSame($title, (string) $rssItem);
        // TimestampableAware trait handles updateTime automatically via event listeners
    }

    public function testLinkSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $link = 'https://example.com/article/test-article';

        $rssItem->setLink($link);

        $this->assertSame($link, $rssItem->getLink());
        // TimestampableAware trait handles updateTime automatically via event listeners
    }

    public function testDescriptionSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $description = 'This is a test article description.';

        $rssItem->setDescription($description);
        $this->assertSame($description, $rssItem->getDescription());

        $rssItem->setDescription(null);
        $this->assertNull($rssItem->getDescription());
    }

    public function testContentSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $content = '<p>This is the full article content.</p>';

        $rssItem->setContent($content);
        $this->assertSame($content, $rssItem->getContent());

        $rssItem->setContent(null);
        $this->assertNull($rssItem->getContent());
    }

    public function testGuidSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $guid = 'unique-article-guid-123';
        // TimestampableAware trait handles updateTime automatically via event listeners

        $rssItem->setGuid($guid);

        $this->assertSame($guid, $rssItem->getGuid());
        // TimestampableAware trait through event listeners auto-manages timestamps in real environment
    }

    public function testPublishTimeSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $publishTime = new \DateTimeImmutable('2023-01-15 10:30:00');

        $rssItem->setPublishTime($publishTime);
        $this->assertSame($publishTime, $rssItem->getPublishTime());

        $rssItem->setPublishTime(null);
        $this->assertNull($rssItem->getPublishTime());
    }

    public function testRssFeedSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $rssFeed = new RssFeed();
        $rssFeed->setName('Test Feed');
        $rssFeed->setUrl('https://example.com/feed.xml');
        // TimestampableAware trait handles updateTime automatically via event listeners

        $rssItem->setRssFeed($rssFeed);

        $this->assertSame($rssFeed, $rssItem->getRssFeed());
        // TimestampableAware trait through event listeners auto-manages timestamps in real environment
    }

    public function testCreateTimeSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $createTime = new \DateTimeImmutable('2023-01-01 12:00:00');

        $rssItem->setCreateTime($createTime);
        $this->assertSame($createTime, $rssItem->getCreateTime());
    }

    public function testUpdateTimeSetterAndGetter(): void
    {
        $rssItem = new RssItem();
        $updateTime = new \DateTimeImmutable('2023-01-15 15:30:00');

        $rssItem->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $rssItem->getUpdateTime());
    }

    public function testCompleteRssItemScenario(): void
    {
        $rssFeed = new RssFeed();
        $rssFeed->setName('Tech News Feed');
        $rssFeed->setUrl('https://technews.example.com/feed.xml');

        $rssItem = new RssItem();
        $rssItem->setTitle('New PHP Release Available');
        $rssItem->setLink('https://technews.example.com/new-php-release');
        $rssItem->setDescription('PHP team announces new version with exciting features.');
        $rssItem->setContent('<p>The PHP development team has released a new version...</p>');
        $rssItem->setGuid('tech-news-php-release-2023');
        $rssItem->setPublishTime(new \DateTimeImmutable('2023-01-15 09:00:00'));
        $rssItem->setRssFeed($rssFeed);

        $this->assertSame('New PHP Release Available', $rssItem->getTitle());
        $this->assertSame('https://technews.example.com/new-php-release', $rssItem->getLink());
        $description = $rssItem->getDescription();
        $content = $rssItem->getContent();
        $this->assertNotNull($description);
        $this->assertNotNull($content);
        $this->assertStringContainsString('PHP team announces', $description);
        $this->assertStringContainsString('PHP development team', $content);
        $this->assertSame('tech-news-php-release-2023', $rssItem->getGuid());
        $this->assertSame($rssFeed, $rssItem->getRssFeed());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rssItem->getPublishTime());
        // TimestampableAware trait timestamps (createTime, updateTime) are set by event listeners
        // In test environment, these may be null since event listeners might not be triggered
        $createTime = $rssItem->getCreateTime();
        $updateTime = $rssItem->getUpdateTime();
        if (null !== $createTime) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $createTime);
        }
        if (null !== $updateTime) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);
        }
    }
}
