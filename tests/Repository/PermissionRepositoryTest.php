<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Repository\PermissionRepository;
use Tourze\RoleBasedAccessControlBundle\Repository\RoleRepository;

/**
 * @template-extends AbstractRepositoryTestCase<Permission>
 * @internal
 */
#[CoversClass(PermissionRepository::class)]
#[RunTestsInSeparateProcesses]
class PermissionRepositoryTest extends AbstractRepositoryTestCase
{
    private PermissionRepository $permissionRepository;

    private RoleRepository $roleRepository;

    protected function onSetUp(): void
    {
        // Repository 测试的初始化逻辑
        // 注意：不要在这里加载 DataFixtures，避免与基类测试冲突
        $permissionRepository = self::getContainer()->get(PermissionRepository::class);
        $roleRepository = self::getContainer()->get(RoleRepository::class);

        $this->assertInstanceOf(PermissionRepository::class, $permissionRepository);
        $this->assertInstanceOf(RoleRepository::class, $roleRepository);

        $this->permissionRepository = $permissionRepository;
        $this->roleRepository = $roleRepository;
    }

    protected function createNewEntity(): Permission
    {
        $permission = new Permission();
        $permission->setCode('PERMISSION_TEST_' . uniqid());
        $permission->setName('Test Permission ' . uniqid());
        $permission->setDescription('A test permission for unit testing');

        return $permission;
    }

    public function testFindOneByCode(): void
    {
        // 创建并保存一个权限
        $permission = $this->createNewEntity();
        $code = $permission->getCode();

        $repository = $this->getRepository();
        $repository->save($permission, true);

        // 测试能够通过 code 找到权限
        $foundPermission = $repository->findOneByCode($code);
        $this->assertNotNull($foundPermission);
        $this->assertEquals($code, $foundPermission->getCode());

        // 测试找不到不存在的权限
        $notFound = $repository->findOneByCode('NON_EXISTENT_CODE');
        $this->assertNull($notFound);
    }

    public function testFindPermissionsForRole(): void
    {
        $repository = $this->getRepository();

        // 创建一个角色
        $role = new Role();
        $role->setCode('ROLE_TEST_' . uniqid());
        $role->setName('Test Role');
        $this->roleRepository->save($role, true);

        // 创建权限并关联到角色
        $permission1 = $this->createNewEntity();
        $permission2 = $this->createNewEntity();
        $role->addPermission($permission1);
        $role->addPermission($permission2);

        $repository->save($permission1, false);
        $repository->save($permission2, false);
        $this->roleRepository->save($role, true);

        // 测试查找角色的权限
        $permissions = $repository->findPermissionsForRole($role->getCode());
        $this->assertCount(2, $permissions);
    }

    public function testFindUnassignedPermissions(): void
    {
        $repository = $this->getRepository();

        // 创建一个角色
        $role = new Role();
        $role->setCode('ROLE_TEST_' . uniqid());
        $role->setName('Test Role');
        $this->roleRepository->save($role, true);

        // 创建已分配的权限
        $assignedPermission = $this->createNewEntity();
        $role->addPermission($assignedPermission);
        $repository->save($assignedPermission, false);
        $this->roleRepository->save($role, true);

        // 创建未分配的权限
        $unassignedPermission = $this->createNewEntity();
        $repository->save($unassignedPermission, true);

        // 测试查找未分配给该角色的权限
        $unassignedPermissions = $repository->findUnassignedPermissions();

        // 验证未分配的权限在结果中，已分配的不在
        $unassignedCodes = array_map(fn ($p) => $p->getCode(), $unassignedPermissions);
        $this->assertContains($unassignedPermission->getCode(), $unassignedCodes);
        $this->assertNotContains($assignedPermission->getCode(), $unassignedCodes);
    }

    public function testGetPermissionsWithRoleCount(): void
    {
        $repository = $this->getRepository();

        // 创建权限
        $permission = $this->createNewEntity();
        $repository->save($permission, true);

        // 创建两个角色并分配权限
        $role1 = new Role();
        $role1->setCode('ROLE_TEST_1_' . uniqid());
        $role1->setName('Test Role 1');
        $role1->addPermission($permission);
        $this->roleRepository->save($role1, true);

        $role2 = new Role();
        $role2->setCode('ROLE_TEST_2_' . uniqid());
        $role2->setName('Test Role 2');
        $role2->addPermission($permission);
        $this->roleRepository->save($role2, true);

        // 测试获取权限及其角色数量
        $results = $repository->getPermissionsWithRoleCount();
        $foundPermission = null;

        foreach ($results as $result) {
            if ($result['permission']->getCode() === $permission->getCode()) {
                $foundPermission = $result;
                break;
            }
        }

        $this->assertNotNull($foundPermission);
        $this->assertEquals(2, $foundPermission['roleCount']);
    }

    public function testFindPermissionsForDeletionCheck(): void
    {
        $repository = $this->getRepository();

        // 创建已分配的权限
        $assignedPermission = $this->createNewEntity();
        $repository->save($assignedPermission, true);

        $role = new Role();
        $role->setCode('ROLE_TEST_' . uniqid());
        $role->setName('Test Role');
        $role->addPermission($assignedPermission);
        $this->roleRepository->save($role, true);

        // 创建未分配的权限
        $unassignedPermission = $this->createNewEntity();
        $repository->save($unassignedPermission, true);

        // 测试已分配权限的删除检查
        $assignedCheck = $repository->findPermissionsForDeletionCheck($assignedPermission->getCode());
        $this->assertGreaterThan(0, count($assignedCheck));

        // 测试未分配权限的删除检查
        $unassignedCheck = $repository->findPermissionsForDeletionCheck($unassignedPermission->getCode());
        $this->assertCount(0, $unassignedCheck);
    }

    public function testSearchPermissions(): void
    {
        $repository = $this->getRepository();

        // 创建测试权限
        $permission1 = new Permission();
        $permission1->setCode('PERMISSION_USER_EDIT_' . uniqid());
        $permission1->setName('Edit User');
        $permission1->setDescription('Permission to edit user information');
        $repository->save($permission1, true);

        $permission2 = new Permission();
        $permission2->setCode('PERMISSION_USER_DELETE_' . uniqid());
        $permission2->setName('Delete User');
        $permission2->setDescription('Permission to delete user accounts');
        $repository->save($permission2, true);

        $permission3 = new Permission();
        $permission3->setCode('PERMISSION_ORDER_VIEW_' . uniqid());
        $permission3->setName('View Order');
        $permission3->setDescription('Permission to view orders');
        $repository->save($permission3, true);

        // 测试搜索
        $userPermissions = $repository->searchPermissions('user');
        $this->assertGreaterThanOrEqual(2, count($userPermissions));

        $orderPermissions = $repository->searchPermissions('order');
        $this->assertGreaterThanOrEqual(1, count($orderPermissions));

        // 测试分页
        $paginatedPermissions = $repository->searchPermissions('', 1);
        $this->assertCount(1, $paginatedPermissions);
    }

    public function testFindByCodePattern(): void
    {
        $repository = $this->getRepository();

        // 创建测试权限
        $permission1 = new Permission();
        $permission1->setCode('PERMISSION_ADMIN_ACCESS_' . uniqid());
        $permission1->setName('Admin Access');
        $repository->save($permission1, true);

        $permission2 = new Permission();
        $permission2->setCode('PERMISSION_ADMIN_MANAGE_' . uniqid());
        $permission2->setName('Admin Manage');
        $repository->save($permission2, true);

        $permission3 = new Permission();
        $permission3->setCode('PERMISSION_USER_VIEW_' . uniqid());
        $permission3->setName('User View');
        $repository->save($permission3, true);

        // 测试按代码模式查找
        $adminPermissions = $repository->findByCodePattern('PERMISSION_ADMIN_%');
        $this->assertGreaterThanOrEqual(2, count($adminPermissions));

        $userPermissions = $repository->findByCodePattern('PERMISSION_USER_%');
        $this->assertGreaterThanOrEqual(1, count($userPermissions));
    }

    public function testSaveAndRemove(): void
    {
        $repository = $this->getRepository();

        // 测试保存
        $permission = $this->createNewEntity();
        $code = $permission->getCode();
        $repository->save($permission, true);

        $saved = $repository->findOneByCode($code);
        $this->assertNotNull($saved);

        // 测试删除
        $repository->remove($saved, true);

        $removed = $repository->findOneByCode($code);
        $this->assertNull($removed);
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        // 创建并保存一个权限
        $permission = $this->createNewEntity();
        $code = $permission->getCode();
        $repository->save($permission, true);

        // 确认权限已保存
        $saved = $repository->findOneByCode($code);
        $this->assertNotNull($saved);

        // 测试删除
        $repository->remove($saved, true);

        // 确认权限已删除
        $removed = $repository->findOneByCode($code);
        $this->assertNull($removed);
    }

    /**
     * @return PermissionRepository
     */
    protected function getRepository(): \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository
    {
        return $this->permissionRepository;
    }
}
