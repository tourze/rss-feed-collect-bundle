<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\RSSFeedCollectBundle\DependencyInjection\RSSFeedCollectExtension;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * RSSFeedCollectExtension 测试类
 * @internal
 */
#[CoversClass(RSSFeedCollectExtension::class)]
class RSSFeedCollectExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testExtensionInstantiates(): void
    {
        $extension = new RSSFeedCollectExtension();

        self::assertEquals('rss_feed_collect', $extension->getAlias());

        // 验证 load 方法存在 - 通过反射检查而不是 method_exists()
        $reflection = new \ReflectionClass($extension);
        self::assertTrue($reflection->hasMethod('load'), 'Extension should have load method');
    }

    public function testGetConfigDir(): void
    {
        $extension = new RSSFeedCollectExtension();

        // 使用反射访问受保护的方法
        $reflection = new \ReflectionClass($extension);
        $method = $reflection->getMethod('getConfigDir');
        $method->setAccessible(true);

        $configDir = $method->invoke($extension);

        // 验证配置目录包含正确的路径结构
        self::assertIsString($configDir);
        self::assertStringEndsWith('/Resources/config', $configDir);

        // 验证路径格式正确（应该是绝对路径）
        self::assertStringContainsString('rss-feed-collect-bundle', $configDir);
    }

    public function testConfigDirExists(): void
    {
        $extension = new RSSFeedCollectExtension();

        // 使用反射访问受保护的方法
        $reflection = new \ReflectionClass($extension);
        $method = $reflection->getMethod('getConfigDir');
        $method->setAccessible(true);

        $configDir = $method->invoke($extension);

        // 验证配置目录路径存在
        self::assertIsString($configDir);
        self::assertDirectoryExists($configDir);
    }

    public function testExtensionLoad(): void
    {
        $extension = new RSSFeedCollectExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        // 测试扩展加载不抛出异常
        $this->expectNotToPerformAssertions();

        try {
            $extension->load([], $container);
        } catch (\Exception $e) {
            self::fail('Extension load should not throw exception: ' . $e->getMessage());
        }
    }

    public function testGetAlias(): void
    {
        $extension = new RSSFeedCollectExtension();

        // Extension 别名应该与Bundle名称匹配
        $expectedAlias = 'rss_feed_collect';
        self::assertEquals($expectedAlias, $extension->getAlias());
    }

    public function testExtensionIsAutoExtension(): void
    {
        $extension = new RSSFeedCollectExtension();

        // 验证是 AutoExtension 的子类
        $reflection = new \ReflectionClass($extension);
        $parentClass = $reflection->getParentClass();

        self::assertNotFalse($parentClass, 'Extension should have a parent class');
        self::assertEquals(AutoExtension::class, $parentClass->getName());
    }
}
