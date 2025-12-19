<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\RSSFeedCollectBundle\Entity\ImportJob;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;

final readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('RSS管理')) {
            $item->addChild('RSS管理');
        }

        $rssMenu = $item->getChild('RSS管理');
        if (null === $rssMenu) {
            return;
        }

        $rssMenu->addChild('RSS源管理')
            ->setUri($this->linkGenerator->getCurdListPage(RssFeed::class))
            ->setAttribute('icon', 'fas fa-rss')
        ;

        $rssMenu->addChild('RSS文章管理')
            ->setUri($this->linkGenerator->getCurdListPage(RssItem::class))
            ->setAttribute('icon', 'fas fa-newspaper')
        ;

        $rssMenu->addChild('导入任务管理')
            ->setUri($this->linkGenerator->getCurdListPage(ImportJob::class))
            ->setAttribute('icon', 'fas fa-file-import')
        ;
    }
}
