<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;

/**
 * RssItem 测试数据装配器
 * 为演示目的创建一些示例RSS文章数据
 */
class RssItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 获取百度科技新闻RSS源的引用
        /** @var RssFeed $baiduTechFeed */
        $baiduTechFeed = $this->getReference(RssFeedFixtures::BAIDU_TECH_FEED_REFERENCE, RssFeed::class);

        // 创建一些示例RSS文章数据（使用符合新闻网站格式的URL）
        $sampleItems = [
            [
                'title' => '人工智能技术取得重大突破',
                'link' => 'https://baijiahao.baidu.com/s?id=1234567890123456789',
                'description' => 'AI技术在自然语言处理和图像识别领域取得了重大进展，为未来科技发展奠定了基础。',
                'content' => '<p>据最新报道，人工智能技术在多个关键领域取得了突破性进展...</p>',
                'guid' => 'ai-breakthrough-2025-001',
                'publishTime' => new \DateTimeImmutable('-2 hours'),
            ],
            [
                'title' => '量子计算机商业化进程加速',
                'link' => 'https://baijiahao.baidu.com/s?id=1234567890123456790',
                'description' => '量子计算技术逐渐从实验室走向商业应用，多家科技公司宣布重大投资计划。',
                'content' => '<p>量子计算作为下一代计算技术的代表，正在加速商业化进程...</p>',
                'guid' => 'quantum-computing-2025-002',
                'publishTime' => new \DateTimeImmutable('-4 hours'),
            ],
            [
                'title' => '5G技术推动物联网发展',
                'link' => 'https://baijiahao.baidu.com/s?id=1234567890123456791',
                'description' => '随着5G网络的普及，物联网设备的连接速度和稳定性得到显著提升。',
                'content' => '<p>5G技术的广泛应用正在推动物联网行业的快速发展...</p>',
                'guid' => '5g-iot-development-2025-003',
                'publishTime' => new \DateTimeImmutable('-6 hours'),
            ],
            [
                'title' => '新能源汽车市场持续增长',
                'link' => 'https://baijiahao.baidu.com/s?id=1234567890123456792',
                'description' => '电动汽车销量再创新高，充电基础设施建设也在加速推进。',
                'content' => '<p>新能源汽车市场在政策支持和技术进步的双重推动下持续增长...</p>',
                'guid' => 'ev-market-growth-2025-004',
                'publishTime' => new \DateTimeImmutable('-8 hours'),
            ],
            [
                'title' => '区块链技术在金融领域的应用',
                'link' => 'https://baijiahao.baidu.com/s?id=1234567890123456793',
                'description' => '银行和金融机构正在探索区块链技术在支付、结算和数字货币领域的应用。',
                'content' => '<p>区块链技术正在重塑传统金融业务模式，提高交易效率和安全性...</p>',
                'guid' => 'blockchain-finance-2025-005',
                'publishTime' => new \DateTimeImmutable('-12 hours'),
            ],
        ];

        foreach ($sampleItems as $itemData) {
            $rssItem = new RssItem();
            $rssItem->setTitle($itemData['title']);
            $rssItem->setLink($itemData['link']);
            $rssItem->setDescription($itemData['description']);
            $rssItem->setContent($itemData['content']);
            $rssItem->setGuid($itemData['guid']);
            $rssItem->setPublishTime($itemData['publishTime']);
            $rssItem->setRssFeed($baiduTechFeed);

            $manager->persist($rssItem);
        }

        // 更新RSS源的文章计数
        $baiduTechFeed->setItemsCount(count($sampleItems));
        $manager->persist($baiduTechFeed);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RssFeedFixtures::class,
        ];
    }
}
