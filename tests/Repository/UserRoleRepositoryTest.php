<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;
use Tourze\RoleBasedAccessControlBundle\Repository\RoleRepository;
use Tourze\RoleBasedAccessControlBundle\Repository\UserRoleRepository;

/**
 * @template-extends AbstractRepositoryTestCase<UserRole>
 * @internal
 */
#[CoversClass(UserRoleRepository::class)]
#[RunTestsInSeparateProcesses]
class UserRoleRepositoryTest extends AbstractRepositoryTestCase
{
    private UserRoleRepository $userRoleRepository;

    private RoleRepository $roleRepository;

    protected function onSetUp(): void
    {
        $userRoleRepository = self::getContainer()->get(UserRoleRepository::class);
        $roleRepository = self::getContainer()->get(RoleRepository::class);

        $this->assertInstanceOf(UserRoleRepository::class, $userRoleRepository);
        $this->assertInstanceOf(RoleRepository::class, $roleRepository);

        $this->userRoleRepository = $userRoleRepository;
        $this->roleRepository = $roleRepository;
    }

    protected function createNewEntity(): UserRole
    {
        // 创建一个关联的角色
        $role = new Role();
        $role->setCode('ROLE_TEST_' . uniqid());
        $role->setName('Test Role ' . uniqid());
        $role->setDescription('A test role for user role testing');
        $this->roleRepository->save($role, true);

        // 创建用户角色关联
        $userRole = new UserRole();
        $userRole->setUserId('test_user_' . uniqid());
        $userRole->setRole($role);

        return $userRole;
    }

    public function testFindByUserId(): void
    {
        $repository = $this->getRepository();

        $userId = 'test_user_' . uniqid();

        // 创建两个角色
        $role1 = new Role();
        $role1->setCode('ROLE_USER_TEST_1_' . uniqid());
        $role1->setName('User Test Role 1');
        $this->roleRepository->save($role1, false);

        $role2 = new Role();
        $role2->setCode('ROLE_USER_TEST_2_' . uniqid());
        $role2->setName('User Test Role 2');
        $this->roleRepository->save($role2, true);

        // 为用户分配两个角色
        $userRole1 = new UserRole();
        $userRole1->setUserId($userId);
        $userRole1->setRole($role1);
        $repository->save($userRole1, false);

        $userRole2 = new UserRole();
        $userRole2->setUserId($userId);
        $userRole2->setRole($role2);
        $repository->save($userRole2, true);

        // 测试查找用户的所有角色
        $userRoles = $repository->findByUserId($userId);
        $this->assertCount(2, $userRoles);

        $roleCodes = array_map(fn ($ur) => $ur->getRole()->getCode(), $userRoles);
        $this->assertContains($role1->getCode(), $roleCodes);
        $this->assertContains($role2->getCode(), $roleCodes);

        // 测试不存在的用户
        $emptyUserRoles = $repository->findByUserId('non_existent_user');
        $this->assertCount(0, $emptyUserRoles);
    }

    public function testFindByRoleCode(): void
    {
        $repository = $this->getRepository();

        // 创建一个角色
        $role = new Role();
        $role->setCode('ROLE_FIND_BY_CODE_TEST_' . uniqid());
        $role->setName('Find By Code Test Role');
        $this->roleRepository->save($role, true);

        // 为该角色分配两个用户
        $userRole1 = new UserRole();
        $userRole1->setUserId('user1_' . uniqid());
        $userRole1->setRole($role);
        $repository->save($userRole1, false);

        $userRole2 = new UserRole();
        $userRole2->setUserId('user2_' . uniqid());
        $userRole2->setRole($role);
        $repository->save($userRole2, true);

        // 测试根据角色代码查找所有用户角色关联
        $userRoles = $repository->findByRoleCode($role->getCode());
        $this->assertCount(2, $userRoles);

        $userIds = array_map(fn ($ur) => $ur->getUserId(), $userRoles);
        $this->assertContains($userRole1->getUserId(), $userIds);
        $this->assertContains($userRole2->getUserId(), $userIds);

        // 测试不存在的角色代码
        $emptyUserRoles = $repository->findByRoleCode('NON_EXISTENT_ROLE');
        $this->assertCount(0, $emptyUserRoles);
    }

    public function testFindUserRoleByUserAndRole(): void
    {
        $repository = $this->getRepository();

        $userId = 'test_user_' . uniqid();

        // 创建角色
        $role = new Role();
        $role->setCode('ROLE_USER_AND_ROLE_TEST_' . uniqid());
        $role->setName('User And Role Test');
        $this->roleRepository->save($role, true);

        // 创建用户角色关联
        $userRole = new UserRole();
        $userRole->setUserId($userId);
        $userRole->setRole($role);
        $repository->save($userRole, true);

        // 测试能够找到特定的用户角色关联
        $foundUserRole = $repository->findUserRoleByUserAndRole($userId, $role->getCode());
        $this->assertNotNull($foundUserRole);
        $this->assertEquals($userId, $foundUserRole->getUserId());
        $this->assertEquals($role->getCode(), $foundUserRole->getRole()->getCode());

        // 测试找不到不存在的关联
        $notFound1 = $repository->findUserRoleByUserAndRole('non_existent_user', $role->getCode());
        $this->assertNull($notFound1);

        $notFound2 = $repository->findUserRoleByUserAndRole($userId, 'NON_EXISTENT_ROLE');
        $this->assertNull($notFound2);
    }

    public function testGetUsersWithRoleCount(): void
    {
        $repository = $this->getRepository();

        $userId1 = 'user_with_one_role_' . uniqid();
        $userId2 = 'user_with_two_roles_' . uniqid();

        // 创建角色
        $role1 = new Role();
        $role1->setCode('ROLE_COUNT_TEST_1_' . uniqid());
        $role1->setName('Role Count Test 1');
        $this->roleRepository->save($role1, false);

        $role2 = new Role();
        $role2->setCode('ROLE_COUNT_TEST_2_' . uniqid());
        $role2->setName('Role Count Test 2');
        $this->roleRepository->save($role2, true);

        // 用户1只有一个角色
        $userRole1 = new UserRole();
        $userRole1->setUserId($userId1);
        $userRole1->setRole($role1);
        $repository->save($userRole1, false);

        // 用户2有两个角色
        $userRole2a = new UserRole();
        $userRole2a->setUserId($userId2);
        $userRole2a->setRole($role1);
        $repository->save($userRole2a, false);

        $userRole2b = new UserRole();
        $userRole2b->setUserId($userId2);
        $userRole2b->setRole($role2);
        $repository->save($userRole2b, true);

        // 获取用户及其角色数量统计
        $results = $repository->getUsersWithRoleCount();
        $this->assertIsArray($results);

        // 查找我们创建的用户
        $user1Found = false;
        $user2Found = false;

        foreach ($results as $result) {
            if ($result['userId'] === $userId1) {
                $this->assertEquals(1, $result['roleCount']);
                $user1Found = true;
            }
            if ($result['userId'] === $userId2) {
                $this->assertEquals(2, $result['roleCount']);
                $user2Found = true;
            }
        }

        $this->assertTrue($user1Found);
        $this->assertTrue($user2Found);
    }

    public function testFindRecentAssignments(): void
    {
        $repository = $this->getRepository();

        // 创建角色
        $role = new Role();
        $role->setCode('ROLE_RECENT_TEST_' . uniqid());
        $role->setName('Recent Assignment Test Role');
        $this->roleRepository->save($role, true);

        // 创建第一个用户角色关联
        $userRole1 = new UserRole();
        $userRole1->setUserId('user1_' . uniqid());
        $userRole1->setRole($role);
        $repository->save($userRole1, true);

        // 显式设置更晚的时间，确保时间顺序正确
        $userRole2 = new UserRole();
        $userRole2->setUserId('user2_' . uniqid());
        $userRole2->setRole($role);
        $userRole2->setAssignTime(new \DateTimeImmutable('+1 second'));
        $repository->save($userRole2, true);

        // 测试查找最近的分配记录（默认限制50个）
        $recentAssignments = $repository->findRecentAssignments();
        $this->assertGreaterThanOrEqual(2, count($recentAssignments));

        // 测试自定义限制
        $limitedAssignments = $repository->findRecentAssignments(1);
        $this->assertCount(1, $limitedAssignments);
    }

    public function testCountUsersByRoleCode(): void
    {
        $repository = $this->getRepository();

        // 创建角色
        $role = new Role();
        $role->setCode('ROLE_COUNT_USERS_TEST_' . uniqid());
        $role->setName('Count Users Test Role');
        $this->roleRepository->save($role, true);

        // 初始应该没有用户
        $count = $repository->countUsersByRoleCode($role->getCode());
        $this->assertEquals(0, $count);

        // 添加用户角色关联
        $userRole1 = new UserRole();
        $userRole1->setUserId('user1_' . uniqid());
        $userRole1->setRole($role);
        $repository->save($userRole1, false);

        $userRole2 = new UserRole();
        $userRole2->setUserId('user2_' . uniqid());
        $userRole2->setRole($role);
        $repository->save($userRole2, true);

        // 现在应该有2个不同的用户
        $count = $repository->countUsersByRoleCode($role->getCode());
        $this->assertEquals(2, $count);

        // 测试同一用户多次分配相同角色（应该只算一次）
        $userRole3 = new UserRole();
        $userRole3->setUserId($userRole1->getUserId()); // 重复用户
        $userRole3->setRole($role);

        // 由于数据库约束，这应该会失败，但如果成功了，计数应该还是2
        try {
            $repository->save($userRole3, true);
            $count = $repository->countUsersByRoleCode($role->getCode());
            $this->assertEquals(2, $count); // 仍然是2，因为使用DISTINCT
        } catch (\Exception $e) {
            // 预期的约束违反，忽略
        }

        // 测试不存在的角色代码
        $nonExistentCount = $repository->countUsersByRoleCode('NON_EXISTENT_ROLE');
        $this->assertEquals(0, $nonExistentCount);
    }

    public function testFindUsersWithMultipleRoles(): void
    {
        $repository = $this->getRepository();

        $userWithMultipleRoles = 'user_multiple_' . uniqid();
        $userWithSingleRole = 'user_single_' . uniqid();

        // 创建角色
        $role1 = new Role();
        $role1->setCode('ROLE_MULTIPLE_1_' . uniqid());
        $role1->setName('Multiple Role Test 1');
        $this->roleRepository->save($role1, false);

        $role2 = new Role();
        $role2->setCode('ROLE_MULTIPLE_2_' . uniqid());
        $role2->setName('Multiple Role Test 2');
        $this->roleRepository->save($role2, false);

        $role3 = new Role();
        $role3->setCode('ROLE_MULTIPLE_3_' . uniqid());
        $role3->setName('Multiple Role Test 3');
        $this->roleRepository->save($role3, true);

        // 用户1有多个角色
        $userRole1a = new UserRole();
        $userRole1a->setUserId($userWithMultipleRoles);
        $userRole1a->setRole($role1);
        $repository->save($userRole1a, false);

        $userRole1b = new UserRole();
        $userRole1b->setUserId($userWithMultipleRoles);
        $userRole1b->setRole($role2);
        $repository->save($userRole1b, false);

        $userRole1c = new UserRole();
        $userRole1c->setUserId($userWithMultipleRoles);
        $userRole1c->setRole($role3);
        $repository->save($userRole1c, false);

        // 用户2只有一个角色
        $userRole2 = new UserRole();
        $userRole2->setUserId($userWithSingleRole);
        $userRole2->setRole($role1);
        $repository->save($userRole2, true);

        // 查找有多个角色的用户
        $usersWithMultipleRoles = $repository->findUsersWithMultipleRoles();
        $this->assertIsArray($usersWithMultipleRoles);

        // 查找有多个角色的用户
        $foundUser = null;
        foreach ($usersWithMultipleRoles as $userInfo) {
            if ($userInfo['userId'] === $userWithMultipleRoles) {
                $foundUser = $userInfo;
                break;
            }
        }

        $this->assertNotNull($foundUser);
        $this->assertEquals(3, $foundUser['roleCount']);
        $this->assertCount(3, $foundUser['roles']);
        $this->assertContains($role1->getCode(), $foundUser['roles']);
        $this->assertContains($role2->getCode(), $foundUser['roles']);
        $this->assertContains($role3->getCode(), $foundUser['roles']);

        // 验证只有单一角色的用户不在结果中
        $singleRoleUserIds = array_column($usersWithMultipleRoles, 'userId');
        $this->assertNotContains($userWithSingleRole, $singleRoleUserIds);
    }

    public function testSaveAndRemove(): void
    {
        $repository = $this->getRepository();

        // 创建角色
        $role = new Role();
        $role->setCode('ROLE_SAVE_REMOVE_TEST_' . uniqid());
        $role->setName('Save Remove Test Role');
        $this->roleRepository->save($role, true);

        $userId = 'test_user_' . uniqid();

        // 测试保存
        $userRole = new UserRole();
        $userRole->setUserId($userId);
        $userRole->setRole($role);
        $repository->save($userRole, true);

        $saved = $repository->findUserRoleByUserAndRole($userId, $role->getCode());
        $this->assertNotNull($saved);
        $this->assertEquals($userId, $saved->getUserId());

        // 测试第二个用户的保存
        $userRole2 = new UserRole();
        $userId2 = 'test_user_2_' . uniqid();
        $userRole2->setUserId($userId2);
        $userRole2->setRole($role);
        $repository->save($userRole2, true);

        // 验证第二个用户的保存
        $saved2 = $repository->findUserRoleByUserAndRole($userId2, $role->getCode());
        $this->assertNotNull($saved2);
        $this->assertEquals($userId2, $saved2->getUserId());

        // 测试删除 - 需要重新获取实体
        self::getEntityManager()->clear();
        $savedForRemove = $repository->findUserRoleByUserAndRole($userId, $role->getCode());
        $this->assertNotNull($savedForRemove);
        $repository->remove($savedForRemove, true);
        self::getEntityManager()->clear();
        $removed = $repository->findUserRoleByUserAndRole($userId, $role->getCode());
        $this->assertNull($removed);

        // 测试不立即刷新的删除 - 需要重新获取实体
        self::getEntityManager()->clear();
        $nowSavedForRemove = $repository->findUserRoleByUserAndRole($userId2, $role->getCode());
        $this->assertNotNull($nowSavedForRemove);
        $repository->remove($nowSavedForRemove, false);

        // 删除前应该还能找到
        $notYetRemoved = $repository->findUserRoleByUserAndRole($userId2, $role->getCode());
        $this->assertNotNull($notYetRemoved);

        // 手动刷新后应该删除
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
        $nowRemoved = $repository->findUserRoleByUserAndRole($userId2, $role->getCode());
        $this->assertNull($nowRemoved);
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        // 创建角色
        $role = new Role();
        $role->setCode('ROLE_REMOVE_TEST_' . uniqid());
        $role->setName('Remove Test Role');
        $this->roleRepository->save($role, true);

        $userId = 'test_remove_user_' . uniqid();

        // 创建并保存 UserRole
        $userRole = new UserRole();
        $userRole->setUserId($userId);
        $userRole->setRole($role);
        $repository->save($userRole, true);

        // 确认已保存
        $saved = $repository->findUserRoleByUserAndRole($userId, $role->getCode());
        $this->assertNotNull($saved);

        // 测试删除
        $repository->remove($saved, true);

        // 确认已删除
        $removed = $repository->findUserRoleByUserAndRole($userId, $role->getCode());
        $this->assertNull($removed);
    }

    /**
     * @return UserRoleRepository
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->userRoleRepository;
    }
}
