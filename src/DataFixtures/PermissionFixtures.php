<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;

final class PermissionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $permissions = [
            ['code' => 'PERMISSION_USER_VIEW', 'name' => '查看用户', 'description' => '查看用户信息的权限'],
            ['code' => 'PERMISSION_USER_EDIT', 'name' => '编辑用户', 'description' => '编辑用户信息的权限'],
            ['code' => 'PERMISSION_USER_DELETE', 'name' => '删除用户', 'description' => '删除用户的权限'],
            ['code' => 'PERMISSION_ROLE_MANAGE', 'name' => '管理角色', 'description' => '管理角色权限的权限'],
        ];

        foreach ($permissions as $permissionData) {
            $permission = new Permission();
            $permission->setCode($permissionData['code']);
            $permission->setName($permissionData['name']);
            $permission->setDescription($permissionData['description']);

            $manager->persist($permission);
            $this->addReference($permissionData['code'], $permission);
        }

        $manager->flush();
    }
}
