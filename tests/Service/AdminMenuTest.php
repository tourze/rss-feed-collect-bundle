<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Tests\Service;

use Knp\Menu\ItemInterface;
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
    private ItemInterface $item;
    private ItemInterface $rssMenu;
    private ItemInterface $childItem;

    protected function onSetUp(): void
    {
        $this->item = $this->createMock(ItemInterface::class);
        $this->rssMenu = $this->createMock(ItemInterface::class);
        $this->childItem = $this->createMock(ItemInterface::class);

        // 设置默认的 mock 返回值
        $this->childItem->method('setUri')->willReturn($this->childItem);
        $this->childItem->method('setAttribute')->willReturn($this->childItem);
    }

    public function testAdminMenuServiceIsRegistered(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testAdminMenuImplementsMenuProviderInterface(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(MenuProviderInterface::class, $adminMenu);
    }

    public function testInvokeMethodDoesNotThrowException(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 配置基本的 mock 行为
        $this->item->expects($this->exactly(2))
            ->method('getChild')
            ->with('RSS管理')
            ->willReturnCallback(function ($name) {
                static $callCount = 0;
                $callCount++;
                return $callCount === 1 ? null : $this->rssMenu;
            });

        $this->item->expects($this->once())
            ->method('addChild')
            ->with('RSS管理')
            ->willReturn($this->rssMenu);

        $this->rssMenu->expects($this->exactly(3))
            ->method('addChild')
            ->willReturn($this->childItem);

        // 如果没有异常抛出，测试通过
        $this->assertTrue(true);
        ($adminMenu)($this->item);
    }

    public function testAdminMenuCreatesRssManagementMenuWhenNotExists(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 模拟 RSS 管理菜单不存在的情况
        $this->item->expects($this->exactly(2))
            ->method('getChild')
            ->with('RSS管理')
            ->willReturnCallback(function ($name) {
                static $callCount = 0;
                $callCount++;
                return $callCount === 1 ? null : $this->rssMenu;
            });

        $this->item->expects($this->once())
            ->method('addChild')
            ->with('RSS管理')
            ->willReturn($this->rssMenu);

        $this->rssMenu->expects($this->exactly(3))
            ->method('addChild')
            ->willReturnCallback(function ($name) {
                // 验证三个子菜单项都被添加
                $this->assertContains($name, ['RSS源管理', 'RSS文章管理', '导入任务管理']);
                return $this->childItem;
            });

        ($adminMenu)($this->item);
    }

    public function testAdminMenuUsesExistingRssMenu(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 模拟 RSS 管理菜单已存在
        $this->item->expects($this->exactly(2))
            ->method('getChild')
            ->with('RSS管理')
            ->willReturn($this->rssMenu);

        // 不应该添加新的 RSS 管理菜单
        $this->item->expects($this->never())
            ->method('addChild');

        $this->rssMenu->expects($this->exactly(3))
            ->method('addChild')
            ->willReturnCallback(function ($name) {
                // 验证三个子菜单项都被添加
                $this->assertContains($name, ['RSS源管理', 'RSS文章管理', '导入任务管理']);
                return $this->childItem;
            });

        ($adminMenu)($this->item);
    }

    public function testAdminMenuHandlesNullRssMenuAfterAdd(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 模拟添加菜单后 getChild 仍然返回 null 的情况
        $this->item->expects($this->exactly(2))
            ->method('getChild')
            ->with('RSS管理')
            ->willReturn(null);

        $this->item->expects($this->once())
            ->method('addChild')
            ->with('RSS管理')
            ->willReturn($this->rssMenu);

        // 由于 getChild 第二次返回 null，不应该尝试添加子菜单
        $this->rssMenu->expects($this->never())
            ->method('addChild');

        ($adminMenu)($this->item);
    }

    public function testAdminMenuServiceHasCorrectDependencies(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
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

    public function testReadOnlyServiceDesign(): void
    {
        // 验证服务是 readonly 的，符合不可变设计
        $reflection = new \ReflectionClass(AdminMenu::class);
        $this->assertTrue($reflection->isReadOnly(), 'AdminMenu service should be readonly');

        // 验证构造函数参数也是 readonly
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);

        $linkGeneratorParam = $parameters[0];
        $this->assertSame('linkGenerator', $linkGeneratorParam->getName());
    }
}