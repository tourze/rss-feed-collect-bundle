<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;

/**
 * RSS Feed 核心业务服务
 */
final readonly class RssFeedService
{
    public function __construct(
        private RssFeedRepository $rssFeedRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 创建单个RSS Feed
     *
     * @param array{name: string, url: string, description?: string, category?: string, isActive?: bool} $feedData
     * @throws \InvalidArgumentException 当URL不合法或已存在时
     */
    public function createFeed(array $feedData): RssFeed
    {
        $url = $feedData['url'];
        $name = $feedData['name'];

        if ('' === $name || null === $name) {
            throw new \InvalidArgumentException('Feed name cannot be empty');
        }

        if ('' === $url || null === $url) {
            throw new \InvalidArgumentException('Feed URL cannot be empty');
        }

        $this->validateUrl($url);
        $this->checkUrlUniqueness($url);

        $now = new \DateTimeImmutable();

        $rssFeed = new RssFeed();
        $rssFeed->setName(trim($name));
        $rssFeed->setUrl(trim($url));
        $rssFeed->setDescription($feedData['description'] ?? null);
        $rssFeed->setCategory($feedData['category'] ?? null);
        $rssFeed->setIsActive($feedData['isActive'] ?? true);
        $rssFeed->setCreateTime($now);
        $rssFeed->setUpdateTime($now);

        $this->rssFeedRepository->save($rssFeed, true);

        return $rssFeed;
    }

    /**
     * 验证URL格式
     *
     * @throws \InvalidArgumentException 当URL格式不正确时
     */
    public function validateUrl(string $url): void
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL format: {$url}");
        }

        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !in_array(strtolower($parsedUrl['scheme']), ['http', 'https'], true)) {
            throw new \InvalidArgumentException("URL must use HTTP or HTTPS protocol: {$url}");
        }

        if (!isset($parsedUrl['host']) || '' === $parsedUrl['host']) {
            throw new \InvalidArgumentException("URL must contain a valid host: {$url}");
        }
    }

    /**
     * 检查URL唯一性
     *
     * @throws \InvalidArgumentException 当URL已存在时
     */
    public function checkUrlUniqueness(string $url): void
    {
        if ($this->rssFeedRepository->existsByUrl($url)) {
            throw new \InvalidArgumentException("RSS Feed with URL '{$url}' already exists");
        }
    }

    /**
     * 批量创建RSS Feed
     *
     * @param array<array{name: string, url: string, description?: string, category?: string, isActive?: bool}> $feedsData
     * @return array{successful: RssFeed[], failed: array<array{data: array<string, mixed>, error: string}>}
     */
    public function batchCreateFeeds(array $feedsData): array
    {
        $successful = [];
        $failed = [];

        foreach ($feedsData as $feedData) {
            try {
                $url = $feedData['url'];

                // 检查URL是否已存在
                if ($this->rssFeedRepository->existsByUrl($url)) {
                    $failed[] = [
                        'data' => $feedData,
                        'error' => "URL already exists: {$url}",
                    ];
                    continue;
                }

                $feed = $this->createFeedWithoutFlush($feedData);
                $successful[] = $feed;
            } catch (\InvalidArgumentException $e) {
                $failed[] = [
                    'data' => $feedData,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ([] !== $successful) {
            $this->rssFeedRepository->batchInsert($successful);
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * 创建Feed但不立即flush到数据库（用于批量操作）
     *
     * @param array{name: string, url: string, description?: string, category?: string, isActive?: bool} $feedData
     * @throws \InvalidArgumentException 当数据不合法时
     */
    private function createFeedWithoutFlush(array $feedData): RssFeed
    {
        $url = $feedData['url'];
        $name = $feedData['name'];

        if ('' === $name || null === $name) {
            throw new \InvalidArgumentException('Feed name cannot be empty');
        }

        if ('' === $url || null === $url) {
            throw new \InvalidArgumentException('Feed URL cannot be empty');
        }

        $this->validateUrl($url);

        $now = new \DateTimeImmutable();

        $rssFeed = new RssFeed();
        $rssFeed->setName(trim($name));
        $rssFeed->setUrl(trim($url));
        $rssFeed->setDescription($feedData['description'] ?? null);
        $rssFeed->setCategory($feedData['category'] ?? null);
        $rssFeed->setIsActive($feedData['isActive'] ?? true);
        $rssFeed->setCreateTime($now);
        $rssFeed->setUpdateTime($now);

        return $rssFeed;
    }

    /**
     * 更新RSS Feed
     *
     * @param array<string, mixed> $updateData
     */
    public function updateFeed(RssFeed $rssFeed, array $updateData): RssFeed
    {
        $this->updateFeedName($rssFeed, $updateData);
        $this->updateFeedUrl($rssFeed, $updateData);
        $this->updateFeedDescription($rssFeed, $updateData);
        $this->updateFeedCategory($rssFeed, $updateData);
        $this->updateFeedActiveStatus($rssFeed, $updateData);

        $rssFeed->setUpdateTime(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $rssFeed;
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function updateFeedName(RssFeed $rssFeed, array $updateData): void
    {
        if (!isset($updateData['name'])) {
            return;
        }

        if (!is_string($updateData['name'])) {
            throw new \InvalidArgumentException('Feed name must be a string');
        }

        $name = trim($updateData['name']);
        if ('' === $name) {
            throw new \InvalidArgumentException('Feed name cannot be empty');
        }

        $rssFeed->setName($name);
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function updateFeedUrl(RssFeed $rssFeed, array $updateData): void
    {
        if (!isset($updateData['url'])) {
            return;
        }

        if (!is_string($updateData['url'])) {
            throw new \InvalidArgumentException('Feed URL must be a string');
        }

        $url = trim($updateData['url']);
        if ('' === $url) {
            throw new \InvalidArgumentException('Feed URL cannot be empty');
        }

        $this->validateUrl($url);

        if ($url !== $rssFeed->getUrl()) {
            $this->checkUrlUniqueness($url);
            $rssFeed->setUrl($url);
        }
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function updateFeedDescription(RssFeed $rssFeed, array $updateData): void
    {
        if (isset($updateData['description'])) {
            $description = $updateData['description'];
            if (!is_string($description) && !is_null($description)) {
                throw new \InvalidArgumentException('Feed description must be a string or null');
            }
            $rssFeed->setDescription($description);
        }
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function updateFeedCategory(RssFeed $rssFeed, array $updateData): void
    {
        if (isset($updateData['category'])) {
            $category = $updateData['category'];
            if (!is_string($category) && !is_null($category)) {
                throw new \InvalidArgumentException('Feed category must be a string or null');
            }
            $rssFeed->setCategory($category);
        }
    }

    /**
     * @param array<string, mixed> $updateData
     */
    private function updateFeedActiveStatus(RssFeed $rssFeed, array $updateData): void
    {
        if (isset($updateData['isActive'])) {
            $rssFeed->setIsActive((bool) $updateData['isActive']);
        }
    }

    /**
     * 删除RSS Feed
     */
    public function deleteFeed(RssFeed $rssFeed): void
    {
        $this->rssFeedRepository->remove($rssFeed, true);
    }

    /**
     * 根据URL查找RSS Feed
     */
    public function findByUrl(string $url): ?RssFeed
    {
        return $this->rssFeedRepository->findByUrl($url);
    }

    /**
     * 获取所有激活的RSS Feed
     *
     * @return RssFeed[]
     */
    public function findActiveFeeds(): array
    {
        return $this->rssFeedRepository->findActiveFeeds();
    }

    /**
     * 激活RSS Feed
     */
    public function activateFeed(RssFeed $rssFeed): void
    {
        $rssFeed->setIsActive(true);
        $rssFeed->setUpdateTime(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    /**
     * 停用RSS Feed
     */
    public function deactivateFeed(RssFeed $rssFeed): void
    {
        $rssFeed->setIsActive(false);
        $rssFeed->setUpdateTime(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}
