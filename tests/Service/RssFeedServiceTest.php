<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;
use Tourze\RSSFeedCollectBundle\Service\RssFeedService;

/**
 * @internal
 */
#[CoversClass(RssFeedService::class)]
#[RunTestsInSeparateProcesses]
class RssFeedServiceTest extends AbstractIntegrationTestCase
{
    private RssFeedRepository $repository;

    private RssFeedService $service;

    public function testCreateFeed(): void
    {
        $feedData = [
            'name' => 'Test RSS Feed',
            'url' => 'https://example.com/rss.xml',
            'description' => 'Test description',
            'category' => 'Technology',
            'isActive' => true,
        ];

        $feed = $this->service->createFeed($feedData);

        $this->assertInstanceOf(RssFeed::class, $feed);
        $this->assertEquals('Test RSS Feed', $feed->getName());
        $this->assertEquals('https://example.com/rss.xml', $feed->getUrl());
        $this->assertEquals('Test description', $feed->getDescription());
        $this->assertEquals('Technology', $feed->getCategory());
        $this->assertTrue($feed->isActive());
    }

    public function testCreateFeedWithMinimalData(): void
    {
        $feedData = [
            'name' => 'Minimal Feed',
            'url' => 'https://example.com/minimal.xml',
        ];

        $feed = $this->service->createFeed($feedData);

        $this->assertEquals('Minimal Feed', $feed->getName());
        $this->assertEquals('https://example.com/minimal.xml', $feed->getUrl());
        $this->assertNull($feed->getDescription());
        $this->assertNull($feed->getCategory());
        $this->assertTrue($feed->isActive()); // 默认为true
    }

    public function testCreateFeedThrowsExceptionForEmptyName(): void
    {
        $feedData = [
            'name' => '',
            'url' => 'https://example.com/rss.xml',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Feed name cannot be empty');

        $this->service->createFeed($feedData);
    }

    public function testCreateFeedThrowsExceptionForEmptyUrl(): void
    {
        $feedData = [
            'name' => 'Test Feed',
            'url' => '',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Feed URL cannot be empty');

        $this->service->createFeed($feedData);
    }

    public function testCreateFeedThrowsExceptionForDuplicateUrl(): void
    {
        $feedData1 = [
            'name' => 'First Feed',
            'url' => 'https://example.com/same.xml',
        ];

        $feedData2 = [
            'name' => 'Second Feed',
            'url' => 'https://example.com/same.xml',
        ];

        $this->service->createFeed($feedData1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("RSS Feed with URL 'https://example.com/same.xml' already exists");

        $this->service->createFeed($feedData2);
    }

    public function testValidateUrl(): void
    {
        // 有效URL不应该抛出异常
        $this->service->validateUrl('https://example.com/rss.xml');
        $this->service->validateUrl('http://example.com/feed.rss');

        // 断言执行到这里表示没有抛出异常
        $this->expectNotToPerformAssertions();
    }

    public function testValidateUrlThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format: invalid-url');

        $this->service->validateUrl('invalid-url');
    }

    public function testValidateUrlThrowsExceptionForInvalidScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL must use HTTP or HTTPS protocol: ftp://example.com/rss.xml');

        $this->service->validateUrl('ftp://example.com/rss.xml');
    }

    public function testValidateUrlThrowsExceptionForMissingHost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format: https://');

        $this->service->validateUrl('https://');
    }

    public function testBatchCreateFeeds(): void
    {
        $feedsData = [
            [
                'name' => 'Feed 1',
                'url' => 'https://example1.com/rss.xml',
                'category' => 'Tech',
            ],
            [
                'name' => 'Feed 2',
                'url' => 'https://example2.com/rss.xml',
                'category' => 'News',
            ],
            [
                'name' => '', // 这个会失败
                'url' => 'https://example3.com/rss.xml',
            ],
            [
                'name' => 'Feed 4',
                'url' => 'invalid-url', // 这个也会失败
            ],
        ];

        $result = $this->service->batchCreateFeeds($feedsData);

        $this->assertCount(2, $result['successful']);
        $this->assertCount(2, $result['failed']);

        // 检查成功创建的feeds
        $this->assertEquals('Feed 1', $result['successful'][0]->getName());
        $this->assertEquals('Feed 2', $result['successful'][1]->getName());

        // 检查失败的原因
        $this->assertStringContainsString('Feed name cannot be empty', $result['failed'][0]['error']);
        $this->assertStringContainsString('Invalid URL format', $result['failed'][1]['error']);
    }

    public function testBatchCreateFeedsWithDuplicateUrl(): void
    {
        // 先创建一个feed
        $existingFeed = [
            'name' => 'Existing Feed',
            'url' => 'https://existing.com/rss.xml',
        ];
        $this->service->createFeed($existingFeed);

        $feedsData = [
            [
                'name' => 'New Feed 1',
                'url' => 'https://new1.com/rss.xml',
            ],
            [
                'name' => 'Duplicate Feed',
                'url' => 'https://existing.com/rss.xml', // 这个URL已存在
            ],
        ];

        $result = $this->service->batchCreateFeeds($feedsData);

        $this->assertCount(1, $result['successful']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('URL already exists', $result['failed'][0]['error']);
    }

    public function testUpdateFeed(): void
    {
        $feed = $this->service->createFeed([
            'name' => 'Original Name',
            'url' => 'https://original.com/rss.xml',
            'description' => 'Original description',
            'category' => 'Original',
            'isActive' => true,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'category' => 'Updated Category',
            'isActive' => false,
        ];

        $updatedFeed = $this->service->updateFeed($feed, $updateData);

        $this->assertEquals('Updated Name', $updatedFeed->getName());
        $this->assertEquals('Updated description', $updatedFeed->getDescription());
        $this->assertEquals('Updated Category', $updatedFeed->getCategory());
        $this->assertFalse($updatedFeed->isActive());
        $this->assertEquals('https://original.com/rss.xml', $updatedFeed->getUrl()); // URL没变
    }

    public function testUpdateFeedUrl(): void
    {
        $feed = $this->service->createFeed([
            'name' => 'Test Feed',
            'url' => 'https://original.com/rss.xml',
        ]);

        $updateData = ['url' => 'https://updated.com/rss.xml'];
        $updatedFeed = $this->service->updateFeed($feed, $updateData);

        $this->assertEquals('https://updated.com/rss.xml', $updatedFeed->getUrl());
    }

    public function testUpdateFeedThrowsExceptionForEmptyName(): void
    {
        $feed = $this->service->createFeed([
            'name' => 'Test Feed',
            'url' => 'https://test.com/rss.xml',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Feed name cannot be empty');

        $this->service->updateFeed($feed, ['name' => '']);
    }

    public function testFindByUrl(): void
    {
        $feedData = [
            'name' => 'Test Feed',
            'url' => 'https://test.com/rss.xml',
        ];

        $createdFeed = $this->service->createFeed($feedData);
        $foundFeed = $this->service->findByUrl('https://test.com/rss.xml');

        $this->assertNotNull($foundFeed);
        $this->assertEquals($createdFeed->getId(), $foundFeed->getId());
    }

    public function testFindByUrlReturnsNullForNonExistentUrl(): void
    {
        $foundFeed = $this->service->findByUrl('https://nonexistent.com/rss.xml');
        $this->assertNull($foundFeed);
    }

    public function testFindActiveFeeds(): void
    {
        // 手动清理 RssFeed 实体，避免 DataFixtures 影响
        self::getEntityManager()->createQuery('DELETE FROM ' . RssFeed::class)->execute();
        self::getEntityManager()->flush();

        $this->service->createFeed([
            'name' => 'Active Feed 1',
            'url' => 'https://active1.com/rss.xml',
            'isActive' => true,
        ]);

        $this->service->createFeed([
            'name' => 'Inactive Feed',
            'url' => 'https://inactive.com/rss.xml',
            'isActive' => false,
        ]);

        $this->service->createFeed([
            'name' => 'Active Feed 2',
            'url' => 'https://active2.com/rss.xml',
            'isActive' => true,
        ]);

        $activeFeeds = $this->service->findActiveFeeds();

        $this->assertCount(2, $activeFeeds);
        foreach ($activeFeeds as $feed) {
            $this->assertTrue($feed->isActive());
        }
    }

    public function testActivateFeed(): void
    {
        $feed = $this->service->createFeed([
            'name' => 'Test Feed',
            'url' => 'https://test.com/rss.xml',
            'isActive' => false,
        ]);

        $this->service->activateFeed($feed);

        $this->assertTrue($feed->isActive());
    }

    public function testDeactivateFeed(): void
    {
        $feed = $this->service->createFeed([
            'name' => 'Test Feed',
            'url' => 'https://test.com/rss.xml',
            'isActive' => true,
        ]);

        $this->service->deactivateFeed($feed);

        $this->assertFalse($feed->isActive());
    }

    public function testDeleteFeed(): void
    {
        $feed = $this->service->createFeed([
            'name' => 'Test Feed',
            'url' => 'https://test.com/rss.xml',
        ]);

        $feedId = $feed->getId();
        $this->service->deleteFeed($feed);

        $deletedFeed = $this->repository->find($feedId);
        $this->assertNull($deletedFeed);
    }

    public function testCheckUrlUniqueness(): void
    {
        $this->service->createFeed([
            'name' => 'Existing Feed',
            'url' => 'https://existing.com/rss.xml',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("RSS Feed with URL 'https://existing.com/rss.xml' already exists");

        $this->service->checkUrlUniqueness('https://existing.com/rss.xml');
    }

    public function testCheckUrlUniquenessPassesForUniqueUrl(): void
    {
        $this->service->createFeed([
            'name' => 'Existing Feed',
            'url' => 'https://existing.com/rss.xml',
        ]);

        // 这不应该抛出异常
        $this->service->checkUrlUniqueness('https://unique.com/rss.xml');

        // 断言执行到这里表示没有抛出异常
        $this->expectNotToPerformAssertions();
    }

    protected function onSetUp(): void
    {
        /** @var RssFeedRepository $repository */
        $repository = self::getService(RssFeedRepository::class);
        $this->repository = $repository;
        /** @var RssFeedService $service */
        $service = self::getService(RssFeedService::class);
        $this->service = $service;
    }

    protected function onTearDown(): void
    {
        // 清理测试数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\RSSFeedCollectBundle\Entity\RssFeed')->execute();
    }
}
