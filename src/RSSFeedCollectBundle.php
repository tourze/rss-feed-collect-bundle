<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;

/**
 * RSS Feed Collection Bundle
 *
 * 提供RSS Feed收集、管理和OPML导入导出功能
 */
class RSSFeedCollectBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            MonologBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
        ];
    }
}
