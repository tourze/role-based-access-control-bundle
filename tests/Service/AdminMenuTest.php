<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Service;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;
use Tourze\RoleBasedAccessControlBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private FactoryInterface $factory;

    protected function onSetUp(): void
    {
        $this->factory = new MenuFactory();
    }

    public function testAdminMenuCanBeInstantiated(): void
    {
        // 从容器获取AdminMenu服务
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testInvokeWithoutLinkGeneratorDoesNothing(): void
    {
        // 测试无LinkGenerator的行为
        // 创建一个没有LinkGenerator的AdminMenu实例
        // 这需要直接实例化，因为我们需要测试特定的依赖注入场景（null依赖）
        // 容器无法保证注入null，因此这是合理的例外情况
        // @phpstan-ignore integrationTest.noDirectInstantiationOfCoveredClass
        $adminMenu = new AdminMenu(null);
        $rootMenu = new MenuItem('root', $this->factory);

        // 调用前菜单应该为空
        $this->assertNull($rootMenu->getChild('权限管理'));

        $adminMenu($rootMenu);

        // 调用后菜单仍应该为空（因为没有LinkGenerator）
        $this->assertNull($rootMenu->getChild('权限管理'));
    }

    public function testInvokeCreatesRbacMenuStructure(): void
    {
        // 创建匿名类 LinkGenerator
        $linkGenerator = new class implements LinkGeneratorInterface {
            public function getCurdListPage(string $entityClass): string
            {
                return match ($entityClass) {
                    Role::class => '/admin/rbac/role',
                    Permission::class => '/admin/rbac/permission',
                    UserRole::class => '/admin/rbac/user-role',
                    default => '/default/path',
                };
            }

            public function extractEntityFqcn(string $url): ?string
            {
                return null;
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // Mock implementation - do nothing
            }
        };

        // 注入mock服务到容器
        static::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器获取服务
        $adminMenu = self::getService(AdminMenu::class);
        $rootMenu = new MenuItem('root', $this->factory);

        // 调用前菜单应该为空
        $this->assertNull($rootMenu->getChild('权限管理'));

        $adminMenu($rootMenu);

        // 验证菜单结构被正确创建
        $rbacMenu = $rootMenu->getChild('权限管理');
        $this->assertInstanceOf(ItemInterface::class, $rbacMenu);

        // 验证子菜单项
        $roleMenu = $rbacMenu->getChild('角色管理');
        $this->assertInstanceOf(ItemInterface::class, $roleMenu);
        $this->assertSame('/admin/rbac/role', $roleMenu->getUri());
        $this->assertSame('fas fa-users-cog', $roleMenu->getAttribute('icon'));

        $permissionMenu = $rbacMenu->getChild('权限管理');
        $this->assertInstanceOf(ItemInterface::class, $permissionMenu);
        $this->assertSame('/admin/rbac/permission', $permissionMenu->getUri());
        $this->assertSame('fas fa-key', $permissionMenu->getAttribute('icon'));

        $userRoleMenu = $rbacMenu->getChild('用户角色关联');
        $this->assertInstanceOf(ItemInterface::class, $userRoleMenu);
        $this->assertSame('/admin/rbac/user-role', $userRoleMenu->getUri());
        $this->assertSame('fas fa-link', $userRoleMenu->getAttribute('icon'));
    }

    public function testInvokeWithExistingRbacMenu(): void
    {
        // 创建匿名类 LinkGenerator
        $linkGenerator = new class implements LinkGeneratorInterface {
            public function getCurdListPage(string $entityClass): string
            {
                return match ($entityClass) {
                    Role::class => '/admin/rbac/role',
                    Permission::class => '/admin/rbac/permission',
                    UserRole::class => '/admin/rbac/user-role',
                    default => '/default/path',
                };
            }

            public function extractEntityFqcn(string $url): ?string
            {
                return null;
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // Mock implementation - do nothing
            }
        };

        // 注入mock服务到容器
        static::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器获取服务
        $adminMenu = self::getService(AdminMenu::class);
        $rootMenu = new MenuItem('root', $this->factory);

        // 预先创建权限管理菜单
        $existingRbacMenu = $rootMenu->addChild('权限管理');
        $this->assertSame($existingRbacMenu, $rootMenu->getChild('权限管理'));

        $adminMenu($rootMenu);

        // 验证使用了现有的菜单项
        $rbacMenu = $rootMenu->getChild('权限管理');
        $this->assertSame($existingRbacMenu, $rbacMenu);

        // 验证子菜单项被正确添加
        $this->assertInstanceOf(ItemInterface::class, $rbacMenu->getChild('角色管理'));
        $this->assertInstanceOf(ItemInterface::class, $rbacMenu->getChild('权限管理'));
        $this->assertInstanceOf(ItemInterface::class, $rbacMenu->getChild('用户角色关联'));
    }

    public function testLinkGeneratorUrlGeneration(): void
    {
        // 测试不同实体类的URL生成
        $linkGenerator = new class implements LinkGeneratorInterface {
            public function getCurdListPage(string $entityClass): string
            {
                return match ($entityClass) {
                    Role::class => '/custom/role/path',
                    Permission::class => '/custom/permission/path',
                    UserRole::class => '/custom/user-role/path',
                    default => '/default/path',
                };
            }

            public function extractEntityFqcn(string $url): ?string
            {
                return null;
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // Mock implementation - do nothing
            }
        };

        // 注入mock服务到容器
        static::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器获取服务
        $adminMenu = self::getService(AdminMenu::class);
        $rootMenu = new MenuItem('root', $this->factory);

        $adminMenu($rootMenu);

        $rbacMenu = $rootMenu->getChild('权限管理');
        $this->assertInstanceOf(ItemInterface::class, $rbacMenu);

        // 验证自定义URL被正确设置
        $roleMenu = $rbacMenu->getChild('角色管理');
        $this->assertInstanceOf(ItemInterface::class, $roleMenu);
        $this->assertSame('/custom/role/path', $roleMenu->getUri());

        $permissionMenu = $rbacMenu->getChild('权限管理');
        $this->assertInstanceOf(ItemInterface::class, $permissionMenu);
        $this->assertSame('/custom/permission/path', $permissionMenu->getUri());

        $userRoleMenu = $rbacMenu->getChild('用户角色关联');
        $this->assertInstanceOf(ItemInterface::class, $userRoleMenu);
        $this->assertSame('/custom/user-role/path', $userRoleMenu->getUri());
    }

    public function testMenuItemProperties(): void
    {
        // 测试菜单项的具体属性设置
        $linkGenerator = new class implements LinkGeneratorInterface {
            public function getCurdListPage(string $entityClass): string
            {
                return '/test/url';
            }

            public function extractEntityFqcn(string $url): ?string
            {
                return null;
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // Mock implementation - do nothing
            }
        };

        // 注入mock服务到容器
        static::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        // 从容器获取服务
        $adminMenu = self::getService(AdminMenu::class);
        $rootMenu = new MenuItem('root', $this->factory);

        $adminMenu($rootMenu);

        $rbacMenu = $rootMenu->getChild('权限管理');
        $this->assertInstanceOf(ItemInterface::class, $rbacMenu);

        // 验证所有子菜单的图标属性
        $expectedMenus = [
            '角色管理' => 'fas fa-users-cog',
            '权限管理' => 'fas fa-key',
            '用户角色关联' => 'fas fa-link',
        ];

        foreach ($expectedMenus as $menuName => $expectedIcon) {
            $menuItem = $rbacMenu->getChild($menuName);
            $this->assertInstanceOf(ItemInterface::class, $menuItem);
            $this->assertSame($expectedIcon, $menuItem->getAttribute('icon'));
            $this->assertSame('/test/url', $menuItem->getUri());
        }
    }
}
