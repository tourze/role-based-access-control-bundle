<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;

/**
 * RBAC权限管理菜单服务
 */
final readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private ?LinkGeneratorInterface $linkGenerator = null,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $this->linkGenerator) {
            return;
        }

        if (null === $item->getChild('权限管理')) {
            $item->addChild('权限管理');
        }

        $rbacMenu = $item->getChild('权限管理');

        if (null === $rbacMenu) {
            return;
        }

        // 角色管理菜单
        $rbacMenu->addChild('角色管理')
            ->setUri($this->linkGenerator->getCurdListPage(Role::class))
            ->setAttribute('icon', 'fas fa-users-cog')
        ;

        // 权限管理菜单
        $rbacMenu->addChild('权限管理')
            ->setUri($this->linkGenerator->getCurdListPage(Permission::class))
            ->setAttribute('icon', 'fas fa-key')
        ;

        // 用户角色关联管理菜单
        $rbacMenu->addChild('用户角色关联')
            ->setUri($this->linkGenerator->getCurdListPage(UserRole::class))
            ->setAttribute('icon', 'fas fa-link')
        ;
    }
}
