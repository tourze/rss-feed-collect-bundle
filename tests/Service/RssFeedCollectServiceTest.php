<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;
use Tourze\RSSFeedCollectBundle\Service\RssFeedCollectService;

/**
 * @internal
 */
#[CoversClass(RssFeedCollectService::class)]
#[RunTestsInSeparateProcesses]
class RssFeedCollectServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    public function testServiceExists(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        $this->assertInstanceOf(RssFeedCollectService::class, $service);
    }

    public function testCollectDueFeeds(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        if (!$service instanceof RssFeedCollectService) {
            throw new \RuntimeException('Expected RssFeedCollectService instance from container');
        }
        $this->assertInstanceOf(RssFeedCollectService::class, $service);

        // 测试返回结果结构
        $result = $service->collectDueFeeds();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertIsInt($result['success']);
        $this->assertIsInt($result['failed']);
        $this->assertIsArray($result['details']);
    }

    public function testCollectFeed(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        if (!$service instanceof RssFeedCollectService) {
            throw new \RuntimeException('Expected RssFeedCollectService instance from container');
        }

        // 创建测试RSS源
        $feedRepository = self::getContainer()->get(RssFeedRepository::class);
        if (!$feedRepository instanceof RssFeedRepository) {
            throw new \RuntimeException('Expected RssFeedRepository instance from container');
        }
        $testFeed = new RssFeed();
        $testFeed->setName('Test Feed');
        $testFeed->setUrl('https://example.com/rss.xml');
        $testFeed->setIsActive(true);
        $testFeed->setCreateTime(new \DateTimeImmutable());
        $testFeed->setUpdateTime(new \DateTimeImmutable());
        $feedRepository->save($testFeed, true);

        // 测试返回结果结构（尽管HTTP请求可能失败）
        $result = $service->collectFeed($testFeed);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('items_count', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsInt($result['items_count']);

        // 如果失败，应该有错误信息
        if (!$result['success']) {
            $this->assertArrayHasKey('error', $result);
            $this->assertIsString($result['error']);
        }
    }

    public function testCollectFeeds(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        if (!$service instanceof RssFeedCollectService) {
            throw new \RuntimeException('Expected RssFeedCollectService instance from container');
        }

        // 测试空数组情况
        $result = $service->collectFeeds([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertSame(0, $result['success']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame([], $result['details']);
    }

    public function testForceCollectFeed(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        if (!$service instanceof RssFeedCollectService) {
            throw new \RuntimeException('Expected RssFeedCollectService instance from container');
        }

        // 创建测试RSS源
        $feedRepository = self::getContainer()->get(RssFeedRepository::class);
        if (!$feedRepository instanceof RssFeedRepository) {
            throw new \RuntimeException('Expected RssFeedRepository instance from container');
        }
        $testFeed = new RssFeed();
        $testFeed->setName('Force Test Feed');
        $testFeed->setUrl('https://example.com/force-rss.xml');
        $testFeed->setIsActive(true);
        $testFeed->setCreateTime(new \DateTimeImmutable());
        $testFeed->setUpdateTime(new \DateTimeImmutable());
        $feedRepository->save($testFeed, true);

        // 测试强制抓取（应该忽略时间间隔）
        $result = $service->forceCollectFeed($testFeed);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('items_count', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsInt($result['items_count']);
    }

    public function testShouldCollectFeed(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        if (!$service instanceof RssFeedCollectService) {
            throw new \RuntimeException('Expected RssFeedCollectService instance from container');
        }

        // 创建新的RSS源（应该需要抓取）
        $newFeed = new RssFeed();
        $newFeed->setName('New Feed');
        $newFeed->setUrl('https://example.com/new-rss.xml');
        $newFeed->setIsActive(true);
        $newFeed->setCollectIntervalMinutes(60);

        $this->assertTrue($service->shouldCollectFeed($newFeed));

        // 创建最近抓取过的RSS源（不应该需要抓取）
        $recentFeed = new RssFeed();
        $recentFeed->setName('Recent Feed');
        $recentFeed->setUrl('https://example.com/recent-rss.xml');
        $recentFeed->setIsActive(true);
        $recentFeed->setCollectIntervalMinutes(60);
        $recentFeed->setLastCollectTime(new \DateTimeImmutable('now'));

        $this->assertFalse($service->shouldCollectFeed($recentFeed));
    }

    public function testGetCollectStatistics(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        if (!$service instanceof RssFeedCollectService) {
            throw new \RuntimeException('Expected RssFeedCollectService instance from container');
        }

        // 测试获取全局统计信息
        $globalStats = $service->getCollectStatistics();
        $this->assertIsArray($globalStats);
        $this->assertArrayHasKey('total_feeds', $globalStats);
        $this->assertArrayHasKey('active_feeds', $globalStats);
        $this->assertArrayHasKey('error_feeds', $globalStats);
        $this->assertArrayHasKey('total_items', $globalStats);
        $this->assertIsInt($globalStats['total_feeds']);
        $this->assertIsInt($globalStats['active_feeds']);
        $this->assertIsInt($globalStats['error_feeds']);
        $this->assertIsInt($globalStats['total_items']);

        // 测试获取特定RSS源的统计信息
        $testFeed = new RssFeed();
        $testFeed->setName('Stats Test Feed');
        $testFeed->setUrl('https://example.com/stats-rss.xml');
        $testFeed->setIsActive(true);
        $testFeed->setStatus('active');
        $testFeed->setItemsCount(42);
        $testFeed->setCollectIntervalMinutes(120);

        $feedStats = $service->getCollectStatistics($testFeed);
        $this->assertIsArray($feedStats);
        $this->assertArrayHasKey('feed_id', $feedStats);
        $this->assertArrayHasKey('feed_name', $feedStats);
        $this->assertArrayHasKey('status', $feedStats);
        $this->assertArrayHasKey('items_count', $feedStats);
        $this->assertArrayHasKey('collect_interval_minutes', $feedStats);
        $this->assertSame('Stats Test Feed', $feedStats['feed_name']);
        $this->assertSame('active', $feedStats['status']);
        $this->assertSame(42, $feedStats['items_count']);
        $this->assertSame(120, $feedStats['collect_interval_minutes']);
    }

    public function testGetRecentItemsForAnalysis(): void
    {
        $service = self::getContainer()->get(RssFeedCollectService::class);
        if (!$service instanceof RssFeedCollectService) {
            throw new \RuntimeException('Expected RssFeedCollectService instance from container');
        }

        // 测试基本调用（返回空数组也是正常的）
        $recentItems = $service->getRecentItemsForAnalysis();
        $this->assertIsArray($recentItems);

        // 测试自定义参数
        $customItems = $service->getRecentItemsForAnalysis(14, 50);
        $this->assertIsArray($customItems);

        // 验证返回的条目数量不超过限制
        $this->assertLessThanOrEqual(50, count($customItems));
    }
}
