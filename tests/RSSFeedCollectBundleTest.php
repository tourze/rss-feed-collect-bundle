<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\RSSFeedCollectBundle\RSSFeedCollectBundle;

/**
 * RSSFeedCollectBundle 测试类
 * @internal
 */
#[CoversClass(RSSFeedCollectBundle::class)]
#[RunTestsInSeparateProcesses]
class RSSFeedCollectBundleTest extends AbstractBundleTestCase
{
    public function testBundleHasGetBundleDependenciesMethod(): void
    {
        $bundleClass = self::getBundleClass();
        self::assertTrue(class_exists($bundleClass), "Bundle class {$bundleClass} does not exist");

        $reflection = new \ReflectionClass($bundleClass);
        self::assertTrue($reflection->hasMethod('getBundleDependencies'), 'Bundle should have getBundleDependencies method');

        $method = $reflection->getMethod('getBundleDependencies');
        self::assertTrue($method->isStatic(), 'getBundleDependencies should be static');
        self::assertTrue($method->isPublic(), 'getBundleDependencies should be public');
    }

    public function testBundleHasCorrectName(): void
    {
        $bundleClass = self::getBundleClass();
        self::assertTrue(class_exists($bundleClass), "Bundle class {$bundleClass} does not exist");

        // Bundle 名称应该从类名派生
        $reflection = new \ReflectionClass($bundleClass);
        $expectedName = $reflection->getShortName();

        // 通过容器获取Bundle实例验证名称
        $bundles = self::getContainer()->getParameter('kernel.bundles');
        self::assertIsArray($bundles);
        self::assertArrayHasKey($expectedName, $bundles);
        self::assertEquals($bundleClass, $bundles[$expectedName]);
    }

    public function testBundleHasRequiredDependencies(): void
    {
        $bundleClass = self::getBundleClass();
        /** @var class-string $bundleClass */
        $dependencies = $bundleClass::getBundleDependencies();

        // 验证包含 Doctrine Bundle 依赖
        self::assertArrayHasKey(DoctrineBundle::class, $dependencies);
        self::assertEquals(['all' => true], $dependencies[DoctrineBundle::class]);

        // 验证包含 Monolog Bundle 依赖
        self::assertArrayHasKey(MonologBundle::class, $dependencies);
        self::assertEquals(['all' => true], $dependencies[MonologBundle::class]);
    }
}
