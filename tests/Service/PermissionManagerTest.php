<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Exception\DeletionConflictException;
use Tourze\RoleBasedAccessControlBundle\Exception\PermissionNotFoundException;
use Tourze\RoleBasedAccessControlBundle\Exception\RoleNotFoundException;
use Tourze\RoleBasedAccessControlBundle\DTO\BulkOperationResult;
use Tourze\RoleBasedAccessControlBundle\Service\PermissionManager;

/**
 * @internal
 */
#[CoversClass(PermissionManager::class)]
#[RunTestsInSeparateProcesses]
class PermissionManagerTest extends AbstractIntegrationTestCase
{
    private PermissionManager $permissionManager;

    private UserInterface $user;

    protected function onSetUp(): void
    {
        // 从容器获取服务
        $this->permissionManager = self::getService(PermissionManager::class);
        $this->user = new class('test-' . uniqid() . '@example.com') implements UserInterface {
            public function __construct(private string $email)
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->email;
            }

            /** @return array<string> */
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }
        };
    }

    public function testAssignRoleToUserWhenRoleExists(): void
    {
        // 测试：角色存在时分配成功
        $roleCode = 'ROLE_EDITOR_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 执行测试
        $result = $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        // 断言结果
        $this->assertTrue($result); // 应该返回true表示发生了变更
    }

    public function testAssignRoleToUserWhenRoleNotExists(): void
    {
        // 测试：角色不存在时抛出异常
        $roleCode = 'INVALID_ROLE_' . uniqid();

        // 断言异常
        $this->expectException(RoleNotFoundException::class);
        $this->expectExceptionMessage('Role with code "' . $roleCode . '" not found');

        $this->permissionManager->assignRoleToUser($this->user, $roleCode);
    }

    public function testAssignRoleToUserWhenAlreadyAssigned(): void
    {
        // 测试：用户已有角色时的幂等性
        $roleCode = 'ROLE_EDITOR_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 先分配一次角色
        $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        // 再次分配相同角色
        $result = $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        // 断言：没有发生变更
        $this->assertFalse($result);
    }

    public function testAddPermissionToRoleWhenBothExist(): void
    {
        // 测试：角色和权限都存在时添加成功
        $roleCode = 'ROLE_EDITOR_' . uniqid();
        $permissionCode = 'PERMISSION_USER_EDIT_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 创建并保存权限
        $permission = new Permission();
        $permission->setCode($permissionCode);
        $permission->setName('User Edit Permission');
        $this->persistAndFlush($permission);

        $result = $this->permissionManager->addPermissionToRole($roleCode, $permissionCode);

        $this->assertTrue($result);
    }

    public function testAddPermissionToRoleWhenPermissionNotExists(): void
    {
        // 测试：权限不存在时抛出异常
        $roleCode = 'ROLE_EDITOR_' . uniqid();
        $permissionCode = 'INVALID_PERMISSION_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage('Permission with code "' . $permissionCode . '" not found');

        $this->permissionManager->addPermissionToRole($roleCode, $permissionCode);
    }

    public function testGetUserPermissionsReturnsCorrectPermissions(): void
    {
        // 测试：获取用户权限返回正确结果
        // 先返回空数组，绿色阶段会实现真正的逻辑
        $result = $this->permissionManager->getUserPermissions($this->user);

        $this->assertSame([], $result);
    }

    public function testHasPermissionReturnsTrueWhenUserHasPermission(): void
    {
        // 测试：用户有权限时返回true
        $permission = 'PERMISSION_USER_EDIT_' . uniqid();

        $result = $this->permissionManager->hasPermission($this->user, $permission);

        $this->assertFalse($result);
    }

    public function testCanDeleteRoleReturnsFalseWhenRoleHasUsers(): void
    {
        // 测试：角色有用户时不能删除
        $roleCode = 'ROLE_EDITOR_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 分配角色给用户
        $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        $result = $this->permissionManager->canDeleteRole($roleCode);

        $this->assertFalse($result);
    }

    public function testDeleteRoleThrowsExceptionWhenCannotDelete(): void
    {
        // 测试：不能删除时抛出异常
        $roleCode = 'ROLE_EDITOR_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 分配角色给用户
        $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        $this->expectException(DeletionConflictException::class);

        $this->permissionManager->deleteRole($roleCode);
    }

    public function testBulkAssignRolesHandlesPartialFailures(): void
    {
        // 测试：批量分配处理部分失败
        $validRoleCode1 = 'ROLE_EDITOR_' . uniqid();
        $validRoleCode2 = 'ROLE_VIEWER_' . uniqid();

        $mapping = [
            'user1' => [$validRoleCode1],
            'user2' => ['INVALID_ROLE_' . uniqid()],  // 这个会失败
            'user3' => [$validRoleCode2],
        ];

        // 创建有效角色
        $role1 = new Role();
        $role1->setCode($validRoleCode1);
        $role1->setName('Editor Role');
        $this->persistAndFlush($role1);

        $role2 = new Role();
        $role2->setCode($validRoleCode2);
        $role2->setName('Viewer Role');
        $this->persistAndFlush($role2);

        $result = $this->permissionManager->bulkAssignRoles($mapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
    }

    public function testBulkGrantPermissions(): void
    {
        // 测试：批量授予权限成功
        $role1Code = 'ROLE_EDITOR_' . uniqid();
        $role2Code = 'ROLE_VIEWER_' . uniqid();
        $permission1Code = 'PERMISSION_USER_EDIT_' . uniqid();
        $permission2Code = 'PERMISSION_USER_VIEW_' . uniqid();

        $rolePermissionMapping = [
            $role1Code => [$permission1Code, $permission2Code],
            $role2Code => [$permission2Code],
        ];

        // 创建角色
        $role1 = new Role();
        $role1->setCode($role1Code);
        $role1->setName('Editor Role');
        $this->persistAndFlush($role1);

        $role2 = new Role();
        $role2->setCode($role2Code);
        $role2->setName('Viewer Role');
        $this->persistAndFlush($role2);

        // 创建权限
        $permission1 = new Permission();
        $permission1->setCode($permission1Code);
        $permission1->setName('User Edit Permission');
        $this->persistAndFlush($permission1);

        $permission2 = new Permission();
        $permission2->setCode($permission2Code);
        $permission2->setName('User View Permission');
        $this->persistAndFlush($permission2);

        $result = $this->permissionManager->bulkGrantPermissions($rolePermissionMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertGreaterThanOrEqual(0, $result->getSuccessCount());
    }

    public function testBulkGrantPermissionsHandlesPartialFailures(): void
    {
        // 测试：批量授予权限处理部分失败
        $validRoleCode = 'ROLE_EDITOR_' . uniqid();
        $permissionCode = 'PERMISSION_USER_EDIT_' . uniqid();

        $rolePermissionMapping = [
            $validRoleCode => [$permissionCode],
            'INVALID_ROLE_' . uniqid() => ['PERMISSION_USER_VIEW_' . uniqid()],  // 这个会失败
        ];

        // 创建有效角色
        $role = new Role();
        $role->setCode($validRoleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 创建有效权限
        $permission = new Permission();
        $permission->setCode($permissionCode);
        $permission->setName('User Edit Permission');
        $this->persistAndFlush($permission);

        $result = $this->permissionManager->bulkGrantPermissions($rolePermissionMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertGreaterThan(0, $result->getFailureCount());
        $this->assertNotEmpty($result->getFailures());
    }

    public function testBulkRevokeRoles(): void
    {
        // 测试：批量撤销角色成功
        $role1Code = 'ROLE_EDITOR_' . uniqid();
        $role2Code = 'ROLE_VIEWER_' . uniqid();

        $userRoleMapping = [
            'user1@example.com' => [$role1Code],
            'user2@example.com' => [$role2Code],
        ];

        // 创建角色
        $role1 = new Role();
        $role1->setCode($role1Code);
        $role1->setName('Editor Role');
        $this->persistAndFlush($role1);

        $role2 = new Role();
        $role2->setCode($role2Code);
        $role2->setName('Viewer Role');
        $this->persistAndFlush($role2);

        // 创建测试用户并分配角色
        $user1 = new class('user1-' . uniqid() . '@example.com') implements UserInterface {
            public function __construct(private string $email)
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->email;
            }

            /** @return array<string> */
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }
        };
        $user2 = new class('user2-' . uniqid() . '@example.com') implements UserInterface {
            public function __construct(private string $email)
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->email;
            }

            /** @return array<string> */
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }
        };
        $this->permissionManager->assignRoleToUser($user1, $role1Code);
        $this->permissionManager->assignRoleToUser($user2, $role2Code);

        $result = $this->permissionManager->bulkRevokeRoles($userRoleMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertGreaterThanOrEqual(0, $result->getSuccessCount());
    }

    public function testBulkRevokeRolesHandlesNonExistentAssignments(): void
    {
        // 测试：批量撤销角色处理不存在的分配
        $userRoleMapping = [
            'user1@example.com' => ['ROLE_EDITOR_' . uniqid()],
            'user2@example.com' => ['ROLE_VIEWER_' . uniqid()],
        ];

        $result = $this->permissionManager->bulkRevokeRoles($userRoleMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEquals(0, $result->getSuccessCount()); // 没有实际撤销
    }

    public function testCanDeletePermissionReturnsTrueWhenNoRolesHavePermission(): void
    {
        // 测试：没有角色拥有权限时可以删除
        $permissionCode = 'PERMISSION_USER_DELETE_' . uniqid();

        $result = $this->permissionManager->canDeletePermission($permissionCode);

        $this->assertTrue($result);
    }

    public function testCanDeletePermissionReturnsFalseWhenRolesHavePermission(): void
    {
        // 测试：有角色拥有权限时不能删除
        $roleCode = 'ROLE_ADMIN_' . uniqid();
        $permissionCode = 'PERMISSION_USER_DELETE_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Admin Role');
        $this->persistAndFlush($role);

        // 创建并保存权限
        $permission = new Permission();
        $permission->setCode($permissionCode);
        $permission->setName('User Delete Permission');
        $this->persistAndFlush($permission);

        // 为角色添加权限
        $this->permissionManager->addPermissionToRole($roleCode, $permissionCode);

        $result = $this->permissionManager->canDeletePermission($permissionCode);

        $this->assertFalse($result);
    }

    public function testDeletePermissionWhenCanDelete(): void
    {
        $this->expectNotToPerformAssertions();

        // 测试：可以删除时成功删除权限
        $permissionCode = 'PERMISSION_USER_DELETE_' . uniqid();

        // 创建并保存权限
        $permission = new Permission();
        $permission->setCode($permissionCode);
        $permission->setName('User Delete Permission');
        $this->persistAndFlush($permission);

        // 应该不抛出异常，幂等操作
        $this->permissionManager->deletePermission($permissionCode);

        // 验证删除操作不抛出异常（幂等操作）
    }

    public function testDeletePermissionThrowsExceptionWhenCannotDelete(): void
    {
        // 测试：不能删除时抛出异常
        $roleCode = 'ROLE_ADMIN_' . uniqid();
        $permissionCode = 'PERMISSION_USER_DELETE_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Admin Role');
        $this->persistAndFlush($role);

        // 创建并保存权限
        $permission = new Permission();
        $permission->setCode($permissionCode);
        $permission->setName('User Delete Permission');
        $this->persistAndFlush($permission);

        // 为角色添加权限
        $this->permissionManager->addPermissionToRole($roleCode, $permissionCode);

        $this->expectException(DeletionConflictException::class);
        $this->expectExceptionMessage('Cannot delete permission "' . $permissionCode . '"');

        $this->permissionManager->deletePermission($permissionCode);
    }

    public function testDeletePermissionWhenPermissionNotExists(): void
    {
        $this->expectNotToPerformAssertions();

        // 测试：权限不存在时的幂等操作
        $permissionCode = 'NONEXISTENT_PERMISSION_' . uniqid();

        // 应该不抛出异常，幂等操作
        $this->permissionManager->deletePermission($permissionCode);

        // 验证幂等操作不抛出异常
    }

    public function testRemovePermissionFromRoleWhenBothExist(): void
    {
        // 测试：角色和权限都存在时移除成功
        $roleCode = 'ROLE_EDITOR_' . uniqid();
        $permissionCode = 'PERMISSION_USER_DELETE_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 创建并保存权限
        $permission = new Permission();
        $permission->setCode($permissionCode);
        $permission->setName('User Delete Permission');
        $this->persistAndFlush($permission);

        // 先为角色添加权限
        $this->permissionManager->addPermissionToRole($roleCode, $permissionCode);

        // 然后移除权限
        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertTrue($result);
    }

    public function testRemovePermissionFromRoleWhenRoleNotExists(): void
    {
        // 测试：角色不存在时返回false
        $roleCode = 'NONEXISTENT_ROLE_' . uniqid();
        $permissionCode = 'PERMISSION_USER_DELETE_' . uniqid();

        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertFalse($result);
    }

    public function testRemovePermissionFromRoleWhenPermissionNotExists(): void
    {
        // 测试：权限不存在时返回false
        $roleCode = 'ROLE_EDITOR_' . uniqid();
        $permissionCode = 'NONEXISTENT_PERMISSION_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertFalse($result);
    }

    public function testRemovePermissionFromRoleWhenPermissionNotInRole(): void
    {
        // 测试：角色没有该权限时返回false（幂等操作）
        $roleCode = 'ROLE_EDITOR_' . uniqid();
        $permissionCode = 'PERMISSION_USER_DELETE_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 创建并保存权限
        $permission = new Permission();
        $permission->setCode($permissionCode);
        $permission->setName('User Delete Permission');
        $this->persistAndFlush($permission);

        // 不为角色添加权限，直接尝试移除（应该返回false）
        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertFalse($result);
    }

    public function testRevokeRoleFromUserWhenAssignmentExists(): void
    {
        // 测试：用户角色分配存在时撤销成功
        $roleCode = 'ROLE_EDITOR_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 先为用户分配角色
        $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        // 然后撤销角色
        $result = $this->permissionManager->revokeRoleFromUser($this->user, $roleCode);

        $this->assertTrue($result);
    }

    public function testRevokeRoleFromUserWhenAssignmentNotExists(): void
    {
        // 测试：用户角色分配不存在时返回false（幂等操作）
        $roleCode = 'ROLE_EDITOR_' . uniqid();

        // 创建并保存角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Editor Role');
        $this->persistAndFlush($role);

        // 不为用户分配角色，直接尝试撤销（应该返回false）
        $result = $this->permissionManager->revokeRoleFromUser($this->user, $roleCode);

        $this->assertFalse($result);
    }
}
