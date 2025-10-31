<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;

/**
 * RssFeed 测试数据装配器
 * 包含真实可测试的RSS源，特别是百度科技新闻源用于实际抓取测试
 */
class RssFeedFixtures extends Fixture
{
    public const BAIDU_TECH_FEED_REFERENCE = 'baidu-tech-feed';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();
        $yesterday = new \DateTimeImmutable('-1 day');
        $oneWeekAgo = new \DateTimeImmutable('-1 week');

        // 创建百度科技新闻RSS源 - 真实可测试的RSS源
        $baiduTechFeed = new RssFeed();
        $baiduTechFeed->setName('百度-科技最新');
        $baiduTechFeed->setUrl('https://news.baidu.com/n?cmd=4&class=technnews&tn=rss');
        $baiduTechFeed->setDescription('百度科技频道最新新闻，提供前沿科技资讯和行业动态');
        $baiduTechFeed->setCategory('科技');
        $baiduTechFeed->setIsActive(true);
        $baiduTechFeed->setCollectIntervalMinutes(30); // 30分钟抓取一次
        $baiduTechFeed->setStatus('active');
        $baiduTechFeed->setItemsCount(0);
        $baiduTechFeed->setCreateTime($now);
        $baiduTechFeed->setUpdateTime($now);
        $manager->persist($baiduTechFeed);

        // 添加引用，供其他 Fixtures 使用
        $this->addReference(self::BAIDU_TECH_FEED_REFERENCE, $baiduTechFeed);

        // 创建技术类RSS源
        $techFeed1 = new RssFeed();
        $techFeed1->setName('PHP 官方博客');
        $techFeed1->setUrl('https://www.php.net/feed.atom');
        $techFeed1->setDescription('PHP 官方博客的最新文章，包含语言特性更新和社区动态');
        $techFeed1->setCategory('技术');
        $techFeed1->setIsActive(true);
        $techFeed1->setCollectIntervalMinutes(120); // 2小时抓取一次
        $techFeed1->setStatus('active');
        $techFeed1->setItemsCount(0);
        $techFeed1->setCreateTime($oneWeekAgo);
        $techFeed1->setUpdateTime($now);
        $manager->persist($techFeed1);

        $techFeed2 = new RssFeed();
        $techFeed2->setName('Symfony 博客');
        $techFeed2->setUrl('https://symfony.com/blog.rss');
        $techFeed2->setDescription('Symfony 框架官方博客，提供最新的框架更新和最佳实践');
        $techFeed2->setCategory('技术');
        $techFeed2->setIsActive(true);
        $techFeed2->setCollectIntervalMinutes(180); // 3小时抓取一次
        $techFeed2->setStatus('active');
        $techFeed2->setItemsCount(0);
        $techFeed2->setCreateTime($oneWeekAgo);
        $techFeed2->setUpdateTime($yesterday);
        $manager->persist($techFeed2);

        // 创建新闻类RSS源
        $newsFeed1 = new RssFeed();
        $newsFeed1->setName('BBC 科技新闻');
        $newsFeed1->setUrl('http://feeds.bbci.co.uk/news/technology/rss.xml');
        $newsFeed1->setDescription('BBC 科技频道的最新科技新闻和趋势分析');
        $newsFeed1->setCategory('新闻');
        $newsFeed1->setIsActive(true);
        $newsFeed1->setCollectIntervalMinutes(60); // 1小时抓取一次
        $newsFeed1->setStatus('active');
        $newsFeed1->setItemsCount(0);
        $newsFeed1->setCreateTime($oneWeekAgo);
        $newsFeed1->setUpdateTime($now);
        $manager->persist($newsFeed1);

        // 创建一个已停用的RSS源
        $inactiveFeed = new RssFeed();
        $inactiveFeed->setName('旧版技术博客');
        $inactiveFeed->setUrl('https://feeds.feedburner.com/oreilly/radar');
        $inactiveFeed->setDescription('已不再维护的技术博客');
        $inactiveFeed->setCategory('技术');
        $inactiveFeed->setIsActive(false);
        $inactiveFeed->setCollectIntervalMinutes(240); // 4小时抓取一次
        $inactiveFeed->setStatus('disabled');
        $inactiveFeed->setItemsCount(0);
        $inactiveFeed->setCreateTime(new \DateTimeImmutable('-1 month'));
        $inactiveFeed->setUpdateTime(new \DateTimeImmutable('-2 weeks'));
        $manager->persist($inactiveFeed);

        // 创建一个没有分类的RSS源
        $uncategorizedFeed = new RssFeed();
        $uncategorizedFeed->setName('个人博客');
        $uncategorizedFeed->setUrl('https://www.hanselman.com/rss');
        $uncategorizedFeed->setDescription('个人技术分享博客');
        $uncategorizedFeed->setCategory(null);
        $uncategorizedFeed->setIsActive(true);
        $uncategorizedFeed->setCollectIntervalMinutes(360); // 6小时抓取一次
        $uncategorizedFeed->setStatus('active');
        $uncategorizedFeed->setItemsCount(0);
        $uncategorizedFeed->setCreateTime($yesterday);
        $uncategorizedFeed->setUpdateTime($yesterday);
        $manager->persist($uncategorizedFeed);

        // 创建博客类RSS源
        $blogFeed = new RssFeed();
        $blogFeed->setName('开发者日志');
        $blogFeed->setUrl('https://martinfowler.com/feed.atom');
        $blogFeed->setDescription('软件开发经验分享和技术心得');
        $blogFeed->setCategory('博客');
        $blogFeed->setIsActive(true);
        $blogFeed->setCollectIntervalMinutes(480); // 8小时抓取一次
        $blogFeed->setStatus('active');
        $blogFeed->setItemsCount(0);
        $blogFeed->setCreateTime($oneWeekAgo);
        $blogFeed->setUpdateTime($now);
        $manager->persist($blogFeed);

        $manager->flush();
    }
}
