<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Service;

use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;

/**
 * RSS源抓取服务接口
 * 定义RSS抓取、解析、去重、入库的核心业务逻辑接口
 */
interface RssFeedCollectServiceInterface
{
    /**
     * 抓取所有需要更新的RSS源 (核心方法)
     *
     * 根据每个RSS源的抓取间隔配置，筛选出需要抓取的源，
     * 并逐一进行抓取、解析和入库操作
     *
     * @return array{success: int, failed: int, details: array<int, array{feed_id: int, feed_name: string, status: string, items_count?: int, error?: string}>}
     */
    public function collectDueFeeds(): array;

    /**
     * 抓取指定RSS源
     *
     * 对指定的RSS源执行HTTP请求、XML解析、去重检查和数据库入库操作
     *
     * @param RssFeed $rssFeed 要抓取的RSS源
     *
     * @return array{success: bool, items_count: int, error?: string}
     */
    public function collectFeed(RssFeed $rssFeed): array;

    /**
     * 检查RSS源是否需要抓取 (时间间隔控制)
     *
     * 基于RSS源的最后抓取时间和配置的抓取间隔，
     * 判断当前是否应该对该源进行抓取
     *
     * @param RssFeed $rssFeed 要检查的RSS源
     *
     * @return bool true表示需要抓取，false表示尚未到抓取时间
     */
    public function shouldCollectFeed(RssFeed $rssFeed): bool;

    /**
     * 强制抓取指定RSS源 (忽略时间间隔)
     *
     * 无论RSS源的抓取间隔设置如何，强制执行一次抓取操作
     * 用于手动触发或管理员命令
     *
     * @param RssFeed $rssFeed 要强制抓取的RSS源
     *
     * @return array{success: bool, items_count: int, error?: string}
     */
    public function forceCollectFeed(RssFeed $rssFeed): array;

    /**
     * 批量抓取指定的RSS源列表
     *
     * 对提供的RSS源列表执行批量抓取操作，
     * 每个源的处理结果会独立记录，单个源的失败不影响其他源
     *
     * @param RssFeed[] $rssFeeds 要抓取的RSS源列表
     * @param bool $force 是否强制抓取(忽略时间间隔)
     *
     * @return array{success: int, failed: int, details: array<int, array{feed_id: int, feed_name: string, status: string, items_count?: int, error?: string}>}
     */
    public function collectFeeds(array $rssFeeds, bool $force = false): array;

    /**
     * 获取RSS源抓取统计信息
     *
     * 返回指定RSS源或所有RSS源的抓取统计数据，
     * 包括总文章数、最后抓取时间、状态等信息
     *
     * @param RssFeed|null $rssFeed 指定RSS源，null表示获取所有源的统计
     *
     * @return array<string, mixed> 统计信息数组
     */
    public function getCollectStatistics(?RssFeed $rssFeed = null): array;

    /**
     * 获取最近的RSS项目用于分析
     *
     * 获取指定时间范围内发布的RSS项目，按发布时间倒序排列
     * 用于支持其他模块的内容分析功能
     *
     * @param int $days 时间范围（天数，默认7天）
     * @param int $limit 最大条目数量（默认100）
     *
     * @return RssItem[] RSS项目列表
     */
    public function getRecentItemsForAnalysis(int $days = 7, int $limit = 100): array;
}
