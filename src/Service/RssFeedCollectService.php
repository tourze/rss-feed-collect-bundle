<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;
use Tourze\RSSFeedCollectBundle\Repository\RssFeedRepository;
use Tourze\RSSFeedCollectBundle\Repository\RssItemRepository;

/**
 * RSS源抓取服务实现
 * 扁平化Service层，直接处理RSS抓取、解析、去重、入库的核心业务逻辑
 * 遵循Linus "Good Taste"原则：消除边界情况，基于数据结构优先设计
 */
#[WithMonologChannel(channel: 'rss_feed_collect')]
final class RssFeedCollectService implements RssFeedCollectServiceInterface
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_USER_AGENT = 'RSS Feed Collector Bot/1.0';

    public function __construct(
        private readonly RssFeedRepository $rssFeedRepository,
        private readonly RssItemRepository $rssItemRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function collectDueFeeds(): array
    {
        $activeFeeds = $this->rssFeedRepository->findActiveFeeds();
        $dueFeeds = array_filter($activeFeeds, fn (RssFeed $feed) => $this->shouldCollectFeed($feed));

        $this->logger->info('Found {count} RSS feeds due for collection', [
            'count' => count($dueFeeds),
            'total_active' => count($activeFeeds),
        ]);

        return $this->collectFeeds($dueFeeds, false);
    }

    public function shouldCollectFeed(RssFeed $rssFeed): bool
    {
        return $rssFeed->isCollectDue();
    }

    public function collectFeeds(array $rssFeeds, bool $force = false): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($rssFeeds as $rssFeed) {
            if (!$force && !$this->shouldCollectFeed($rssFeed)) {
                continue;
            }

            $feedResult = $this->doCollectFeed($rssFeed, $force);

            $detail = [
                'feed_id' => $rssFeed->getId() ?? 0,
                'feed_name' => $rssFeed->getName(),
                'status' => $feedResult['success'] ? 'success' : 'failed',
            ];

            if ($feedResult['success']) {
                $detail['items_count'] = $feedResult['items_count'];
                ++$results['success'];
            } else {
                $detail['error'] = $feedResult['error'] ?? 'Unknown error';
                ++$results['failed'];
            }

            $results['details'][] = $detail;
        }

        return $results;
    }

    /**
     * 执行单个RSS源的抓取操作(内部方法)
     *
     * @param RssFeed $rssFeed 要抓取的RSS源
     * @param bool $force 是否强制抓取
     *
     * @return array{success: bool, items_count: int, error?: string}
     */
    private function doCollectFeed(RssFeed $rssFeed, bool $force): array
    {
        $this->logger->info('Starting RSS feed collection', [
            'feed_id' => $rssFeed->getId(),
            'feed_name' => $rssFeed->getName(),
            'feed_url' => $rssFeed->getUrl(),
            'force' => $force,
        ]);

        try {
            // 1. HTTP抓取RSS内容
            $rssContent = $this->fetchRssContent($rssFeed);

            // 2. 解析RSS XML内容
            $rssItems = $this->parseRssContent($rssContent, $rssFeed);

            // 3. 去重并保存RSS条目
            $savedCount = $this->saveRssItems($rssItems, $rssFeed);

            // 4. 更新RSS源状态
            $this->updateRssFeedStatus($rssFeed, 'active', null, $savedCount);

            $this->logger->info('RSS feed collection completed successfully', [
                'feed_id' => $rssFeed->getId(),
                'items_saved' => $savedCount,
            ]);

            return ['success' => true, 'items_count' => $savedCount];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->updateRssFeedStatus($rssFeed, 'error', $errorMessage, 0);

            $this->logger->error('RSS feed collection failed', [
                'feed_id' => $rssFeed->getId(),
                'error' => $errorMessage,
                'exception' => $e,
            ]);

            return ['success' => false, 'items_count' => 0, 'error' => $errorMessage];
        }
    }

    /**
     * 通过HTTP请求获取RSS内容
     */
    private function fetchRssContent(RssFeed $rssFeed): string
    {
        $envTimeout = $_ENV['RSS_COLLECT_TIMEOUT'] ?? self::DEFAULT_TIMEOUT;
        $timeout = is_numeric($envTimeout) ? (int) $envTimeout : self::DEFAULT_TIMEOUT;
        $userAgent = $_ENV['RSS_COLLECT_USER_AGENT'] ?? self::DEFAULT_USER_AGENT;

        try {
            $response = $this->httpClient->request('GET', $rssFeed->getUrl(), [
                'timeout' => $timeout,
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept' => 'application/rss+xml, application/xml, text/xml',
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException("HTTP request failed with status {$response->getStatusCode()}");
            }

            $content = $response->getContent();

            if ('' === $content) {
                throw new \RuntimeException('Empty RSS content received');
            }

            return $content;
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException("HTTP transport error: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 解析RSS XML内容为RssItem对象数组
     *
     * @return RssItem[]
     */
    private function parseRssContent(string $content, RssFeed $rssFeed): array
    {
        $previousUseErrors = libxml_use_internal_errors(true);

        try {
            $doc = new \DOMDocument();
            $doc->loadXML($content);

            $xpath = new \DOMXPath($doc);
            $items = $xpath->query('//item');

            if (false === $items || 0 === $items->length) {
                return [];
            }

            $rssItems = [];

            foreach ($items as $itemNode) {
                if (!$itemNode instanceof \DOMNode) {
                    continue;
                }
                $rssItem = $this->parseRssItem($itemNode, $xpath, $rssFeed);
                if (null !== $rssItem) {
                    $rssItems[] = $rssItem;
                }
            }

            return $rssItems;
        } finally {
            libxml_use_internal_errors($previousUseErrors);
        }
    }

    /**
     * 解析单个RSS item节点
     */
    private function parseRssItem(\DOMNode $itemNode, \DOMXPath $xpath, RssFeed $rssFeed): ?RssItem
    {
        $title = $this->getNodeValue($xpath, 'title', $itemNode);
        $link = $this->getNodeValue($xpath, 'link', $itemNode);
        $guid = $this->getNodeValue($xpath, 'guid', $itemNode);
        if ('' === $guid) {
            $guid = $link;
        }

        // title和link是必需字段
        if ('' === $title || '' === $link) {
            $this->logger->warning('Skipping RSS item with missing title or link', [
                'title' => $title,
                'link' => $link,
            ]);

            return null;
        }

        $rssItem = new RssItem();
        $rssItem->setTitle($title);
        $rssItem->setLink($link);
        $rssItem->setGuid($guid);
        $rssItem->setRssFeed($rssFeed);

        // 可选字段
        $description = $this->getNodeValue($xpath, 'description', $itemNode);
        if ('' !== $description) {
            $rssItem->setDescription($description);
        }

        $content = $this->getNodeValue($xpath, 'content:encoded', $itemNode);
        if ('' === $content) {
            $content = $this->getNodeValue($xpath, 'content', $itemNode);
        }
        if ('' !== $content) {
            $rssItem->setContent($content);
        }

        $pubDate = $this->getNodeValue($xpath, 'pubDate', $itemNode);
        if ('' !== $pubDate) {
            try {
                $publishTime = new \DateTimeImmutable($pubDate);
                $rssItem->setPublishTime($publishTime);
            } catch (\Exception $e) {
                $this->logger->warning('Invalid publish date format', [
                    'pubDate' => $pubDate,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rssItem;
    }

    /**
     * 获取XML节点值的辅助方法
     */
    private function getNodeValue(\DOMXPath $xpath, string $tagName, \DOMNode $contextNode): string
    {
        $nodes = $xpath->query($tagName, $contextNode);

        if (false === $nodes || 0 === $nodes->length) {
            return '';
        }

        $node = $nodes->item(0);

        return null !== $node && $node instanceof \DOMNode ? trim($node->textContent) : '';
    }

    /**
     * 保存RSS条目到数据库(基于link去重)
     *
     * @param RssItem[] $rssItems
     */
    private function saveRssItems(array $rssItems, RssFeed $rssFeed): int
    {
        $savedCount = 0;

        foreach ($rssItems as $rssItem) {
            try {
                $existingItem = $this->rssItemRepository->findByLink($rssItem->getLink());

                if (null !== $existingItem) {
                    // 更新现有文章
                    $existingItem->setTitle($rssItem->getTitle());
                    $existingItem->setDescription($rssItem->getDescription());
                    $existingItem->setContent($rssItem->getContent());
                    $existingItem->setGuid($rssItem->getGuid());

                    if (null !== $rssItem->getPublishTime()) {
                        $existingItem->setPublishTime($rssItem->getPublishTime());
                    }

                    $this->rssItemRepository->save($existingItem, false);
                } else {
                    // 新增文章
                    $this->rssItemRepository->save($rssItem, false);
                    ++$savedCount;
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to save RSS item', [
                    'link' => $rssItem->getLink(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 批量提交
        $this->rssItemRepository->flush();

        return $savedCount;
    }

    /**
     * 更新RSS源状态信息
     */
    private function updateRssFeedStatus(
        RssFeed $rssFeed,
        string $status,
        ?string $error,
        int $newItemsCount,
    ): void {
        $rssFeed->setStatus($status);
        $rssFeed->setLastError($error);
        $rssFeed->setLastCollectTime(new \DateTimeImmutable());

        if ($newItemsCount > 0) {
            $currentCount = $rssFeed->getItemsCount();
            $rssFeed->setItemsCount($currentCount + $newItemsCount);
        }

        $this->rssFeedRepository->save($rssFeed, true);
    }

    public function collectFeed(RssFeed $rssFeed): array
    {
        return $this->doCollectFeed($rssFeed, false);
    }

    public function forceCollectFeed(RssFeed $rssFeed): array
    {
        return $this->doCollectFeed($rssFeed, true);
    }

    public function getCollectStatistics(?RssFeed $rssFeed = null): array
    {
        if (null !== $rssFeed) {
            return [
                'feed_id' => $rssFeed->getId(),
                'feed_name' => $rssFeed->getName(),
                'status' => $rssFeed->getStatus(),
                'items_count' => $rssFeed->getItemsCount(),
                'last_collect_time' => $rssFeed->getLastCollectTime()?->format('Y-m-d H:i:s'),
                'collect_interval_minutes' => $rssFeed->getCollectIntervalMinutes(),
                'last_error' => $rssFeed->getLastError(),
            ];
        }

        $allFeeds = $this->rssFeedRepository->findAll();
        $totalItems = 0;
        $activeFeeds = 0;
        $errorFeeds = 0;

        foreach ($allFeeds as $feed) {
            $totalItems += $feed->getItemsCount();

            if ('active' === $feed->getStatus()) {
                ++$activeFeeds;
            } elseif ('error' === $feed->getStatus()) {
                ++$errorFeeds;
            }
        }

        return [
            'total_feeds' => count($allFeeds),
            'active_feeds' => $activeFeeds,
            'error_feeds' => $errorFeeds,
            'total_items' => $totalItems,
        ];
    }

    public function getRecentItemsForAnalysis(int $days = 7, int $limit = 100): array
    {
        $cutoffDate = new \DateTimeImmutable(sprintf('-%d days', $days));

        /** @var RssItem[] */
        return $this->rssItemRepository->createQueryBuilder('r')
            ->where('r.publishTime >= :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('r.publishTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
