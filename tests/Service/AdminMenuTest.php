<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\RSSFeedCollectBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 在AbstractEasyAdminMenuTestCase中，onSetUp是可选的
    }

    public function testAdminMenuServiceIsRegistered(): void
    {
        $adminMenu = self::getContainer()->get(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testAdminMenuImplementsMenuProviderInterface(): void
    {
        $adminMenu = self::getContainer()->get(AdminMenu::class);
        $this->assertInstanceOf(MenuProviderInterface::class, $adminMenu);
    }

    public function testAdminMenuCreatesRssManagementMenu(): void
    {
        $adminMenu = self::getContainer()->get(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);

        // 使用简化的集成测试方法，测试服务能正常工作
        // 创建一个简单的测试存根类来验证基本功能
        $testItem = new TestMenuItem();

        // 执行测试 - 主要验证服务不会抛出异常
        $adminMenu($testItem);

        // 验证基本行为：应该尝试添加RSS管理菜单
        $this->assertTrue($testItem->hasAddChildBeenCalled('RSS管理'));
        $this->assertTrue($testItem->hasGetChildBeenCalled('RSS管理'));
    }

    public function testAdminMenuHandlesExistingRssMenu(): void
    {
        $adminMenu = self::getContainer()->get(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);

        // 测试当RSS管理菜单已存在时的行为
        $testItem = new TestMenuItem();
        $existingRssMenu = new TestMenuItem(); // 模拟已存在的RSS菜单
        $testItem->setChildToReturn('RSS管理', $existingRssMenu);

        // 执行测试
        $adminMenu($testItem);

        // 验证调用：应该检查菜单是否存在，但不添加新菜单
        $this->assertTrue($testItem->hasGetChildBeenCalled('RSS管理'));
        $this->assertFalse($testItem->hasAddChildBeenCalled('RSS管理')); // 不应该添加，因为已存在

        // 验证子菜单项被添加到已存在的RSS菜单
        $this->assertTrue($existingRssMenu->hasAddChildBeenCalled('RSS源管理'));
        $this->assertTrue($existingRssMenu->hasAddChildBeenCalled('RSS文章管理'));
        $this->assertTrue($existingRssMenu->hasAddChildBeenCalled('导入任务管理'));
    }

    public function testAdminMenuHandlesNullRssMenu(): void
    {
        $adminMenu = self::getContainer()->get(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);

        // 测试当getChild第二次调用返回null时的行为
        $testItem = new TestMenuItem();
        $testItem->setChildToReturn('RSS管理', null); // 模拟getChild返回null

        // 执行测试
        $adminMenu($testItem);

        // 验证调用：应该先添加RSS管理菜单，然后尝试获取它
        $this->assertTrue($testItem->hasAddChildBeenCalled('RSS管理'));
        $this->assertTrue($testItem->hasGetChildBeenCalled('RSS管理'));

        // 由于getChild第二次调用返回null，方法应该提前返回，不进行后续操作
        // 这验证了方法对null的处理逻辑
        $this->assertEquals(2, $testItem->getGetChildCallCount('RSS管理'));
    }

    public function testAdminMenuServiceHasCorrectDependencies(): void
    {
        $adminMenu = self::getContainer()->get(AdminMenu::class);

        // 验证服务可以正常实例化，说明依赖注入正确
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);

        // 通过反射验证构造函数参数类型
        $reflection = new \ReflectionClass(AdminMenu::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);

        $linkGeneratorParam = $parameters[0];
        $this->assertEquals('linkGenerator', $linkGeneratorParam->getName());

        $paramType = $linkGeneratorParam->getType();
        if ($paramType instanceof \ReflectionNamedType) {
            $this->assertEquals(LinkGeneratorInterface::class, $paramType->getName());
        } else {
            self::fail('Parameter type should be a named type');
        }
    }
}
