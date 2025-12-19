<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;
use Tourze\RSSFeedCollectBundle\Repository\RssItemRepository;

/**
 * RssItemRepository 单元测试
 * @internal
 */
#[CoversClass(RssItemRepository::class)]
#[RunTestsInSeparateProcesses]
class RssItemRepositoryTest extends AbstractRepositoryTestCase
{
    private RssItemRepository $repository;

    protected function createNewEntity(): object
    {
        $rssFeed = $this->createTestRssFeed();
        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->flush();

        return $this->createTestRssItem($rssFeed, 'https://example.com/test-' . uniqid());
    }

    protected function getRepository(): RssItemRepository
    {
        return $this->repository;
    }

    /**
     * @return array<int, array<int, array<int|string, string>|string>>
     */
    public static function findOneBySortOrderProvider(): array
    {
        return [
            // 方法名, 字段名, 排序方向, 期望值
            ['findOneBy', ['title'], ['title' => 'ASC'], 'Test Item'],
        ];
    }

    public function testFindByLink(): void
    {
        $rssFeed = $this->createTestRssFeed();
        $rssItem = $this->createTestRssItem($rssFeed, 'https://example.com/article-1');

        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->persist($rssItem);
        self::getEntityManager()->flush();

        $foundItem = $this->repository->findByLink('https://example.com/article-1');
        $this->assertInstanceOf(RssItem::class, $foundItem);
        $this->assertSame($rssItem->getId(), $foundItem->getId());

        $notFoundItem = $this->repository->findByLink('https://example.com/non-existent');
        $this->assertNull($notFoundItem);
    }

    private function createTestRssFeed(?string $name = null, ?string $url = null): RssFeed
    {
        $rssFeed = new RssFeed();
        $rssFeed->setName($name ?? 'Test Feed ' . uniqid());
        $rssFeed->setUrl($url ?? 'https://test-feed-' . uniqid() . '.example.com/feed.xml');
        $rssFeed->setCreateTime(new \DateTimeImmutable());
        $rssFeed->setUpdateTime(new \DateTimeImmutable());

        return $rssFeed;
    }

    private function createTestRssItem(RssFeed $rssFeed, string $link): RssItem
    {
        $rssItem = new RssItem();
        $rssItem->setTitle('Test Article Title');
        $rssItem->setLink($link);
        $rssItem->setGuid('guid-' . uniqid());
        $rssItem->setRssFeed($rssFeed);
        $rssItem->setPublishTime(new \DateTimeImmutable());
        $rssItem->setCreateTime(new \DateTimeImmutable());

        return $rssItem;
    }

    public function testExistsByLink(): void
    {
        $rssFeed = $this->createTestRssFeed();
        $rssItem = $this->createTestRssItem($rssFeed, 'https://example.com/exists-test');

        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->persist($rssItem);
        self::getEntityManager()->flush();

        $this->assertTrue($this->repository->existsByLink('https://example.com/exists-test'));
        $this->assertFalse($this->repository->existsByLink('https://example.com/non-existent'));
    }

    public function testFindByRssFeed(): void
    {
        $rssFeed1 = $this->createTestRssFeed('Feed 1', 'https://feed1.example.com/feed.xml');

        $rssFeed2 = $this->createTestRssFeed('Feed 2', 'https://feed2.example.com/feed.xml');

        $item1 = $this->createTestRssItem($rssFeed1, 'https://example.com/feed1-item1');
        $item2 = $this->createTestRssItem($rssFeed1, 'https://example.com/feed1-item2');
        $item3 = $this->createTestRssItem($rssFeed2, 'https://example.com/feed2-item1');

        self::getEntityManager()->persist($rssFeed1);
        self::getEntityManager()->persist($rssFeed2);
        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->persist($item3);
        self::getEntityManager()->flush();

        $feed1Items = $this->repository->findByRssFeed($rssFeed1);
        $this->assertCount(2, $feed1Items);

        $feed2Items = $this->repository->findByRssFeed($rssFeed2);
        $this->assertCount(1, $feed2Items);

        // 测试分页
        $limitedItems = $this->repository->findByRssFeed($rssFeed1, 1);
        $this->assertCount(1, $limitedItems);
    }

    public function testCountByRssFeed(): void
    {
        $rssFeed = $this->createTestRssFeed();
        $item1 = $this->createTestRssItem($rssFeed, 'https://example.com/count-test-1');
        $item2 = $this->createTestRssItem($rssFeed, 'https://example.com/count-test-2');

        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->flush();

        $count = $this->repository->countByRssFeed($rssFeed);
        $this->assertSame(2, $count);
    }

    public function testFindRecentItems(): void
    {
        // 清理数据确保测试环境纯净
        self::getEntityManager()->createQuery('DELETE FROM ' . RssItem::class)->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . RssFeed::class)->execute();

        $rssFeed = $this->createTestRssFeed();

        $oldItem = $this->createTestRssItem($rssFeed, 'https://example.com/old-item');
        $oldItem->setPublishTime(new \DateTimeImmutable('-2 days'));

        $newItem = $this->createTestRssItem($rssFeed, 'https://example.com/new-item');
        $newItem->setPublishTime(new \DateTimeImmutable('-1 day'));

        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->persist($oldItem);
        self::getEntityManager()->persist($newItem);
        self::getEntityManager()->flush();

        $recentItems = $this->repository->findRecentItems(10);
        $this->assertCount(2, $recentItems);
        // 应该按发布时间降序排列
        $this->assertSame($newItem->getId(), $recentItems[0]->getId());
        $this->assertSame($oldItem->getId(), $recentItems[1]->getId());
    }

    public function testFindByGuid(): void
    {
        $rssFeed = $this->createTestRssFeed();
        $rssItem = $this->createTestRssItem($rssFeed, 'https://example.com/guid-test');
        $rssItem->setGuid('unique-guid-123');

        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->persist($rssItem);
        self::getEntityManager()->flush();

        $foundItem = $this->repository->findByGuid('unique-guid-123');
        $this->assertInstanceOf(RssItem::class, $foundItem);
        $this->assertSame($rssItem->getId(), $foundItem->getId());

        $notFoundItem = $this->repository->findByGuid('non-existent-guid');
        $this->assertNull($notFoundItem);
    }

    public function testFindByDateRange(): void
    {
        $rssFeed = $this->createTestRssFeed();

        $item1 = $this->createTestRssItem($rssFeed, 'https://example.com/date-test-1');
        $item1->setPublishTime(new \DateTimeImmutable('2023-01-01'));

        $item2 = $this->createTestRssItem($rssFeed, 'https://example.com/date-test-2');
        $item2->setPublishTime(new \DateTimeImmutable('2023-01-15'));

        $item3 = $this->createTestRssItem($rssFeed, 'https://example.com/date-test-3');
        $item3->setPublishTime(new \DateTimeImmutable('2023-02-01'));

        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->persist($item3);
        self::getEntityManager()->flush();

        $itemsInRange = $this->repository->findByDateRange(
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-01-31')
        );

        $this->assertCount(2, $itemsInRange);
    }

    public function testSaveAndRemove(): void
    {
        $rssFeed = $this->createTestRssFeed();
        $rssItem = $this->createTestRssItem($rssFeed, 'https://example.com/save-remove-test');

        self::getEntityManager()->persist($rssFeed);

        $this->repository->save($rssItem, true);

        $this->assertNotNull($rssItem->getId());
        $this->assertTrue($this->repository->existsByLink('https://example.com/save-remove-test'));

        $this->repository->remove($rssItem, true);

        $this->assertFalse($this->repository->existsByLink('https://example.com/save-remove-test'));
    }

    public function testBatchInsert(): void
    {
        $rssFeed = $this->createTestRssFeed();
        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->flush();

        $items = [];
        for ($i = 1; $i <= 5; ++$i) {
            $item = $this->createTestRssItem($rssFeed, "https://example.com/batch-{$i}");
            $items[] = $item;
        }

        $this->repository->batchInsert($items);

        foreach ($items as $item) {
            $this->assertNotNull($item->getId());
        }

        $count = $this->repository->countByRssFeed($rssFeed);
        $this->assertSame(5, $count);
    }

    public function testRemoveOlderThan(): void
    {
        // 清理数据确保测试环境纯净
        self::getEntityManager()->createQuery('DELETE FROM ' . RssItem::class)->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . RssFeed::class)->execute();

        $rssFeed = $this->createTestRssFeed();

        // 创建旧文章（2天前）
        $oldItem1 = $this->createTestRssItem($rssFeed, 'https://example.com/old-1');
        $oldItem1->setCreateTime(new \DateTimeImmutable('-2 days'));

        $oldItem2 = $this->createTestRssItem($rssFeed, 'https://example.com/old-2');
        $oldItem2->setCreateTime(new \DateTimeImmutable('-3 days'));

        // 创建新文章（1小时前）
        $newItem = $this->createTestRssItem($rssFeed, 'https://example.com/new');
        $newItem->setCreateTime(new \DateTimeImmutable('-1 hour'));

        self::getEntityManager()->persist($rssFeed);
        self::getEntityManager()->persist($oldItem1);
        self::getEntityManager()->persist($oldItem2);
        self::getEntityManager()->persist($newItem);
        self::getEntityManager()->flush();

        // 验证初始状态 - 应该有3个文章
        $initialCount = $this->repository->countByRssFeed($rssFeed);
        $this->assertSame(3, $initialCount);

        // 删除1天前创建的文章
        $cutoffDate = new \DateTimeImmutable('-1 day');
        $deletedCount = $this->repository->removeOlderThan($cutoffDate);

        // 应该删除了2个旧文章
        $this->assertSame(2, $deletedCount);

        // 验证只剩下新文章
        $remainingCount = $this->repository->countByRssFeed($rssFeed);
        $this->assertSame(1, $remainingCount);

        // 验证剩下的是新文章
        $remainingItems = $this->repository->findByRssFeed($rssFeed);
        $this->assertCount(1, $remainingItems);
        $this->assertSame($newItem->getLink(), $remainingItems[0]->getLink());
    }

    public function testFlush(): void
    {
        $rssFeed = $this->createTestRssFeed();
        $item = $this->createTestRssItem($rssFeed, 'https://example.com/flush-test');

        self::getEntityManager()->persist($rssFeed);
        $this->repository->save($item, false);

        // 手动调用flush
        $this->repository->flush();

        // 验证数据已持久化
        $foundItem = $this->repository->find($item->getId());
        self::assertNotNull($foundItem);
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RssItemRepository::class);
    }

    protected function onTearDown(): void
    {
        // 清理测试数据
        self::getEntityManager()->close();
    }
}
