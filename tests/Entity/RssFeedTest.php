<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;

/**
 * @internal
 */
#[CoversClass(RssFeed::class)]
class RssFeedTest extends AbstractEntityTestCase
{
    protected function createEntity(): RssFeed
    {
        $rssFeed = new RssFeed();
        $rssFeed->setName('Test Feed - ' . uniqid());
        $rssFeed->setUrl('https://example.com/feed-' . uniqid() . '.xml');
        $rssFeed->setDescription('Test RSS feed description');
        $rssFeed->setCategory('Technology');
        $rssFeed->setStatus('active');
        $rssFeed->setCreateTime(new \DateTimeImmutable());
        $rssFeed->setUpdateTime(new \DateTimeImmutable());

        return $rssFeed;
    }

    /**
     * @return array<int, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            ['name', 'Test RSS Feed'],
            ['url', 'https://example.com/feed.xml'],
            ['description', 'Test description'],
            ['category', 'Technology'],
            ['status', 'active'],
            ['collectIntervalMinutes', 120],
            ['lastCollectTime', new \DateTimeImmutable()],
            ['lastError', 'Test error'],
            ['itemsCount', 100],
            ['createTime', new \DateTimeImmutable()],
            ['updateTime', new \DateTimeImmutable()],
        ];
    }

    public function testCreateRssFeed(): void
    {
        $rssFeed = new RssFeed();

        $this->assertNull($rssFeed->getId());
        $this->assertTrue($rssFeed->isActive());
    }

    public function testSetAndGetName(): void
    {
        $rssFeed = new RssFeed();
        $name = 'Test RSS Feed';

        $rssFeed->setName($name);

        $this->assertSame($name, $rssFeed->getName());
        $this->assertSame($name, $rssFeed->__toString());
    }

    public function testSetAndGetUrl(): void
    {
        $rssFeed = new RssFeed();
        $url = 'https://example.com/feed.xml';

        $rssFeed->setUrl($url);

        $this->assertSame($url, $rssFeed->getUrl());
    }

    public function testSetAndGetDescription(): void
    {
        $rssFeed = new RssFeed();
        $description = 'This is a test RSS feed';

        $rssFeed->setDescription($description);

        $this->assertSame($description, $rssFeed->getDescription());
    }

    public function testSetAndGetCategory(): void
    {
        $rssFeed = new RssFeed();
        $category = 'Technology';

        $rssFeed->setCategory($category);

        $this->assertSame($category, $rssFeed->getCategory());
    }

    public function testSetAndGetIsActive(): void
    {
        $rssFeed = new RssFeed();

        $rssFeed->setIsActive(false);

        $this->assertFalse($rssFeed->isActive());
    }

    public function testSetAndGetCreateTime(): void
    {
        $rssFeed = new RssFeed();
        $createTime = new \DateTimeImmutable('2025-01-01 12:00:00');

        $rssFeed->setCreateTime($createTime);

        $this->assertSame($createTime, $rssFeed->getCreateTime());
    }

    public function testSetAndGetUpdateTime(): void
    {
        $rssFeed = new RssFeed();
        $updateTime = new \DateTimeImmutable('2025-01-01 12:00:00');

        $rssFeed->setUpdateTime($updateTime);

        $this->assertSame($updateTime, $rssFeed->getUpdateTime());
    }

    public function testSetAndGetCollectIntervalMinutes(): void
    {
        $rssFeed = new RssFeed();
        $interval = 120; // 2小时

        $this->assertSame(60, $rssFeed->getCollectIntervalMinutes()); // 默认值

        $rssFeed->setCollectIntervalMinutes($interval);

        $this->assertSame($interval, $rssFeed->getCollectIntervalMinutes());
    }

    public function testSetAndGetLastCollectTime(): void
    {
        $rssFeed = new RssFeed();
        $lastCollectTime = new \DateTimeImmutable('2023-01-15 10:00:00');

        $this->assertNull($rssFeed->getLastCollectTime()); // 默认值

        $rssFeed->setLastCollectTime($lastCollectTime);

        $this->assertSame($lastCollectTime, $rssFeed->getLastCollectTime());

        $rssFeed->setLastCollectTime(null);
        $this->assertNull($rssFeed->getLastCollectTime());
    }

    public function testSetAndGetStatus(): void
    {
        $rssFeed = new RssFeed();

        $this->assertSame('active', $rssFeed->getStatus()); // 默认值

        $rssFeed->setStatus('error');
        $this->assertSame('error', $rssFeed->getStatus());

        $rssFeed->setStatus('disabled');
        $this->assertSame('disabled', $rssFeed->getStatus());
    }

    public function testSetAndGetLastError(): void
    {
        $rssFeed = new RssFeed();
        $errorMessage = 'Connection timeout';

        $this->assertNull($rssFeed->getLastError()); // 默认值

        $rssFeed->setLastError($errorMessage);

        $this->assertSame($errorMessage, $rssFeed->getLastError());

        $rssFeed->setLastError(null);
        $this->assertNull($rssFeed->getLastError());
    }

    public function testSetAndGetItemsCount(): void
    {
        $rssFeed = new RssFeed();

        $this->assertSame(0, $rssFeed->getItemsCount()); // 默认值

        $rssFeed->setItemsCount(100);
        $this->assertSame(100, $rssFeed->getItemsCount());
    }

    public function testIncrementItemsCount(): void
    {
        $rssFeed = new RssFeed();

        $rssFeed->setItemsCount(5);
        $rssFeed->incrementItemsCount();

        $this->assertSame(6, $rssFeed->getItemsCount());

        $rssFeed->incrementItemsCount();
        $this->assertSame(7, $rssFeed->getItemsCount());
    }

    public function testIsCollectDue(): void
    {
        $rssFeed = new RssFeed();

        // 未抓取过，应该返回true
        $this->assertTrue($rssFeed->isCollectDue());

        // 设置最后抓取时间为当前时间，应该返回false
        $rssFeed->setLastCollectTime(new \DateTimeImmutable());
        $this->assertFalse($rssFeed->isCollectDue());

        // 设置最后抓取时间为2小时前，且间隔为60分钟，应该返回true
        $rssFeed->setLastCollectTime(new \DateTimeImmutable('-2 hours'));
        $this->assertTrue($rssFeed->isCollectDue());

        // 状态为非active时，应该返回false
        $rssFeed->setStatus('disabled');
        $this->assertFalse($rssFeed->isCollectDue());

        $rssFeed->setStatus('error');
        $this->assertFalse($rssFeed->isCollectDue());
    }

    public function testIsCollectDueWithCustomInterval(): void
    {
        $rssFeed = new RssFeed();
        $rssFeed->setCollectIntervalMinutes(120); // 2小时间隔

        // 设置最后抓取时间为1小时前，应该返回false
        $rssFeed->setLastCollectTime(new \DateTimeImmutable('-1 hour'));
        $this->assertFalse($rssFeed->isCollectDue());

        // 设置最后抓取时间为3小时前，应该返回true
        $rssFeed->setLastCollectTime(new \DateTimeImmutable('-3 hours'));
        $this->assertTrue($rssFeed->isCollectDue());
    }

    public function testSetAllProperties(): void
    {
        $rssFeed = new RssFeed();
        $name = 'Test Feed';
        $url = 'https://test.com/feed.xml';
        $description = 'Test description';
        $category = 'Test';
        $createTime = new \DateTimeImmutable();

        // 使用分离的语句设置所有属性（符合 void 返回值的 setter）
        $rssFeed->setName($name);
        $rssFeed->setUrl($url);
        $rssFeed->setDescription($description);
        $rssFeed->setCategory($category);
        $rssFeed->setIsActive(true);
        $rssFeed->setCreateTime($createTime);
        $rssFeed->setCollectIntervalMinutes(120);
        $rssFeed->setStatus('active');
        $rssFeed->setItemsCount(50);

        // 验证所有属性都被正确设置
        $this->assertSame($name, $rssFeed->getName());
        $this->assertSame($url, $rssFeed->getUrl());
        $this->assertSame($description, $rssFeed->getDescription());
        $this->assertSame($category, $rssFeed->getCategory());
        $this->assertTrue($rssFeed->isActive());
        $this->assertSame($createTime, $rssFeed->getCreateTime());
        // 注意：updateTime现在由CreateTimeColumn/UpdateTimeColumn属性自动管理，
        // 在实际应用中由Doctrine事件监听器处理，测试环境中需要手动设置
        $this->assertNull($rssFeed->getUpdateTime());
        $this->assertSame(120, $rssFeed->getCollectIntervalMinutes());
        $this->assertSame('active', $rssFeed->getStatus());
        $this->assertSame(50, $rssFeed->getItemsCount());
    }
}
