<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;

/**
 * @internal
 */
#[CoversClass(RssFeedRepository::class)]
#[RunTestsInSeparateProcesses]
class RssFeedRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // AbstractRepositoryTestCase 需要的设置
    }

    protected function createNewEntity(): RssFeed
    {
        $feed = new RssFeed();
        $feed->setName('Test Feed ' . mt_rand(1, 10000));
        $feed->setUrl('https://example' . mt_rand(1, 10000) . '.com/feed.xml');
        $feed->setCreateTime(new \DateTimeImmutable());
        $feed->setUpdateTime(new \DateTimeImmutable());

        return $feed;
    }

    protected function getRepository(): RssFeedRepository
    {
        return self::getService(RssFeedRepository::class);
    }

    public function testFindByUrl(): void
    {
        $feed = $this->createNewEntity();
        $this->getRepository()->save($feed, true);

        $result = $this->getRepository()->findByUrl($feed->getUrl());

        $this->assertSame($feed, $result);
    }

    public function testFindByUrlNotFound(): void
    {
        $result = $this->getRepository()->findByUrl('https://nonexistent.com/feed.xml');

        $this->assertNull($result);
    }

    public function testFindActiveFeeds(): void
    {
        // 清理数据确保测试环境纯净
        self::getEntityManager()->createQuery('DELETE FROM ' . RssFeed::class)->execute();

        $activeFeed = $this->createNewEntity();
        $activeFeed->setIsActive(true);
        $this->getRepository()->save($activeFeed, true);

        $inactiveFeed = $this->createNewEntity();
        $inactiveFeed->setIsActive(false);
        $this->getRepository()->save($inactiveFeed, true);

        $result = $this->getRepository()->findActiveFeeds();

        $this->assertCount(1, $result);
        $this->assertSame($activeFeed, $result[0]);
        $this->assertTrue($result[0]->isActive());
    }

    public function testExistsByUrlTrue(): void
    {
        $feed = $this->createNewEntity();
        $this->getRepository()->save($feed, true);

        $result = $this->getRepository()->existsByUrl($feed->getUrl());

        $this->assertTrue($result);
    }

    public function testExistsByUrlFalse(): void
    {
        $result = $this->getRepository()->existsByUrl('https://nonexistent.com/feed.xml');

        $this->assertFalse($result);
    }

    public function testBatchInsert(): void
    {
        $feeds = [
            $this->createNewEntity(),
            $this->createNewEntity(),
        ];

        $this->getRepository()->batchInsert($feeds);

        foreach ($feeds as $feed) {
            $foundFeed = $this->getRepository()->find($feed->getId());
            $this->assertNotNull($foundFeed);
            $this->assertEquals($feed->getName(), $foundFeed->getName());
        }
    }
}
