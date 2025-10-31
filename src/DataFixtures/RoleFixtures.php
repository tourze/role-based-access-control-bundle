<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;

class RoleFixtures extends Fixture
{
    public const ROLE_SUPER_ADMIN_REFERENCE = 'role-super-admin';
    public const ROLE_ADMIN_REFERENCE = 'role-admin';
    public const ROLE_MANAGER_REFERENCE = 'role-manager';
    public const ROLE_EDITOR_REFERENCE = 'role-editor';
    public const ROLE_USER_REFERENCE = 'role-user';
    public const ROLE_GUEST_REFERENCE = 'role-guest';

    public function load(ObjectManager $manager): void
    {
        // 创建超级管理员角色
        $superAdmin = new Role();
        $superAdmin->setCode('ROLE_SUPER_ADMIN');
        $superAdmin->setName('超级管理员');
        $superAdmin->setDescription('拥有所有权限的最高管理员');
        $manager->persist($superAdmin);
        $this->addReference(self::ROLE_SUPER_ADMIN_REFERENCE, $superAdmin);

        // 创建管理员角色
        $admin = new Role();
        $admin->setCode('ROLE_ADMIN');
        $admin->setName('管理员');
        $admin->setDescription('系统管理员');
        $admin->setParentRoleId($superAdmin->getId());
        $admin->setHierarchyLevel(1);
        $manager->persist($admin);
        $this->addReference(self::ROLE_ADMIN_REFERENCE, $admin);

        // 创建经理角色
        $managerRole = new Role();
        $managerRole->setCode('ROLE_MANAGER');
        $managerRole->setName('经理');
        $managerRole->setDescription('部门经理');
        $managerRole->setParentRoleId($admin->getId());
        $managerRole->setHierarchyLevel(2);
        $manager->persist($managerRole);
        $this->addReference(self::ROLE_MANAGER_REFERENCE, $managerRole);

        // 创建编辑角色
        $editor = new Role();
        $editor->setCode('ROLE_EDITOR');
        $editor->setName('编辑');
        $editor->setDescription('内容编辑员');
        $editor->setParentRoleId($managerRole->getId());
        $editor->setHierarchyLevel(3);
        $manager->persist($editor);
        $this->addReference(self::ROLE_EDITOR_REFERENCE, $editor);

        // 创建普通用户角色
        $user = new Role();
        $user->setCode('ROLE_USER');
        $user->setName('普通用户');
        $user->setDescription('普通注册用户');
        $user->setParentRoleId($editor->getId());
        $user->setHierarchyLevel(4);
        $manager->persist($user);
        $this->addReference(self::ROLE_USER_REFERENCE, $user);

        // 创建访客角色
        $guest = new Role();
        $guest->setCode('ROLE_GUEST');
        $guest->setName('访客');
        $guest->setDescription('未登录访客');
        $guest->setParentRoleId($user->getId());
        $guest->setHierarchyLevel(5);
        $manager->persist($guest);
        $this->addReference(self::ROLE_GUEST_REFERENCE, $guest);

        $manager->flush();
    }
}
