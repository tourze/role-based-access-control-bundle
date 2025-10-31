<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;
use Tourze\RoleBasedAccessControlBundle\Repository\RoleRepository;
use Tourze\RoleBasedAccessControlBundle\Repository\UserRoleRepository;

/**
 * @template-extends AbstractRepositoryTestCase<Role>
 * @internal
 */
#[CoversClass(RoleRepository::class)]
#[RunTestsInSeparateProcesses]
class RoleRepositoryTest extends AbstractRepositoryTestCase
{
    private RoleRepository $roleRepository;

    private UserRoleRepository $userRoleRepository;

    protected function onSetUp(): void
    {
        // Repository 测试的初始化逻辑
        // 注意：不要在这里加载 DataFixtures，避免与基类测试冲突
        $roleRepository = self::getContainer()->get(RoleRepository::class);
        $userRoleRepository = self::getContainer()->get(UserRoleRepository::class);

        $this->assertInstanceOf(RoleRepository::class, $roleRepository);
        $this->assertInstanceOf(UserRoleRepository::class, $userRoleRepository);

        $this->roleRepository = $roleRepository;
        $this->userRoleRepository = $userRoleRepository;
    }

    protected function createNewEntity(): Role
    {
        $role = new Role();
        $role->setCode('ROLE_TEST_' . uniqid());
        $role->setName('Test Role ' . uniqid());
        $role->setDescription('A test role for unit testing');

        return $role;
    }

    public function testFindOneByCode(): void
    {
        $repository = $this->getRepository();

        // 创建并保存一个角色
        $role = $this->createNewEntity();
        $code = $role->getCode();
        $repository->save($role, true);

        // 测试能够通过 code 找到角色
        $foundRole = $repository->findOneByCode($code);
        $this->assertNotNull($foundRole);
        $this->assertEquals($code, $foundRole->getCode());
        $this->assertEquals($role->getName(), $foundRole->getName());

        // 测试找不到不存在的角色
        $notFound = $repository->findOneByCode('NON_EXISTENT_CODE');
        $this->assertNull($notFound);
    }

    public function testCountUsersByRoleCode(): void
    {
        $repository = $this->getRepository();

        // 创建一个角色
        $role = $this->createNewEntity();
        $repository->save($role, true);

        // 初始应该没有用户
        $count = $repository->countUsersByRoleCode($role->getCode());
        $this->assertEquals(0, $count);

        // 创建用户角色关联
        $userRole1 = new UserRole();
        $userRole1->setUserId('user1');
        $userRole1->setRole($role);
        $this->userRoleRepository->save($userRole1, false);

        $userRole2 = new UserRole();
        $userRole2->setUserId('user2');
        $userRole2->setRole($role);
        $this->userRoleRepository->save($userRole2, true);

        // 现在应该有2个用户
        $count = $repository->countUsersByRoleCode($role->getCode());
        $this->assertEquals(2, $count);

        // 测试不存在的角色代码
        $nonExistentCount = $repository->countUsersByRoleCode('NON_EXISTENT_ROLE');
        $this->assertEquals(0, $nonExistentCount);
    }

    public function testGetRolesWithUserCount(): void
    {
        $repository = $this->getRepository();

        // 创建两个角色
        $role1 = $this->createNewEntity();
        $role2 = $this->createNewEntity();
        $repository->save($role1, false);
        $repository->save($role2, true);

        // 为第一个角色分配用户
        $userRole = new UserRole();
        $userRole->setUserId('user1');
        $userRole->setRole($role1);
        $this->userRoleRepository->save($userRole, true);

        // 获取角色及其用户数量
        $results = $repository->getRolesWithUserCount();
        // @phpstan-ignore-next-line method.alreadyNarrowedType
        $this->assertIsArray($results);

        // 查找我们创建的角色
        $role1Found = false;
        $role2Found = false;

        foreach ($results as $result) {
            $role = $result['role']; // Role对象在'role'键
            if ($role->getCode() === $role1->getCode()) {
                $this->assertEquals(1, $result['userCount']);
                $role1Found = true;
            }
            if ($role->getCode() === $role2->getCode()) {
                $this->assertEquals(0, $result['userCount']);
                $role2Found = true;
            }
        }

        $this->assertTrue($role1Found);
        $this->assertTrue($role2Found);
    }

    public function testFindRolesForDeletionCheck(): void
    {
        $repository = $this->getRepository();

        // 创建有用户关联的角色
        $roleWithUsers = $this->createNewEntity();
        $repository->save($roleWithUsers, true);

        $userRole = new UserRole();
        $userRole->setUserId('user1');
        $userRole->setRole($roleWithUsers);
        $this->userRoleRepository->save($userRole, true);

        // 检查有关联用户的角色
        $userIds = $repository->findRolesForDeletionCheck($roleWithUsers->getCode());
        $this->assertCount(1, $userIds);
        $this->assertEquals('user1', $userIds[0]);

        // 创建没有用户关联的角色
        $roleWithoutUsers = $this->createNewEntity();
        $repository->save($roleWithoutUsers, true);

        // 检查没有关联用户的角色
        $emptyUserIds = $repository->findRolesForDeletionCheck($roleWithoutUsers->getCode());
        $this->assertCount(0, $emptyUserIds);

        // 测试不存在的角色
        $nonExistentUserIds = $repository->findRolesForDeletionCheck('NON_EXISTENT_ROLE');
        $this->assertCount(0, $nonExistentUserIds);
    }

    public function testFindByParentRoleId(): void
    {
        $repository = $this->getRepository();

        // 创建父角色
        $parentRole = $this->createNewEntity();
        $parentRole->setParentRoleId(null);
        $parentRole->setHierarchyLevel(0);
        $repository->save($parentRole, true);

        // 创建子角色
        $childRole1 = $this->createNewEntity();
        $childRole1->setParentRoleId($parentRole->getId());
        $childRole1->setHierarchyLevel(1);
        $repository->save($childRole1, false);

        $childRole2 = $this->createNewEntity();
        $childRole2->setParentRoleId($parentRole->getId());
        $childRole2->setHierarchyLevel(1);
        $repository->save($childRole2, true);

        // 查找子角色
        $childRoles = $repository->findByParentRoleId($parentRole->getId());
        $this->assertCount(2, $childRoles);

        $childCodes = array_map(fn ($role) => $role->getCode(), $childRoles);
        $this->assertContains($childRole1->getCode(), $childCodes);
        $this->assertContains($childRole2->getCode(), $childCodes);

        // 查找根角色（无父角色）
        $rootRoles = $repository->findByParentRoleId(null);
        $this->assertGreaterThanOrEqual(1, count($rootRoles));

        $rootCodes = array_map(fn ($role) => $role->getCode(), $rootRoles);
        $this->assertContains($parentRole->getCode(), $rootCodes);

        // 查找不存在的父角色ID
        $noChildRoles = $repository->findByParentRoleId(99999);
        $this->assertCount(0, $noChildRoles);
    }

    public function testSearchRoles(): void
    {
        $repository = $this->getRepository();

        // 创建测试角色
        $adminRole = new Role();
        $adminRole->setCode('ROLE_ADMIN_SEARCH_TEST');
        $adminRole->setName('Administrator Search Test');
        $adminRole->setDescription('Admin role for search testing');
        $repository->save($adminRole, false);

        $userRole = new Role();
        $userRole->setCode('ROLE_USER_SEARCH_TEST');
        $userRole->setName('User Search Test');
        $userRole->setDescription('User role for search testing');
        $repository->save($userRole, false);

        $managerRole = new Role();
        $managerRole->setCode('ROLE_MANAGER_TEST');
        $managerRole->setName('Manager Test');
        $managerRole->setDescription('Manager role for testing');
        $repository->save($managerRole, true);

        // 搜索包含 "ADMIN" 的角色
        $adminResults = $repository->searchRoles('ADMIN');
        $adminCodes = array_map(fn ($role) => $role->getCode(), $adminResults);
        $this->assertContains('ROLE_ADMIN_SEARCH_TEST', $adminCodes);

        // 搜索包含 "USER" 的角色
        $userResults = $repository->searchRoles('USER');
        $userCodes = array_map(fn ($role) => $role->getCode(), $userResults);
        $this->assertContains('ROLE_USER_SEARCH_TEST', $userCodes);

        // 搜索包含 "Test" 的角色（应该匹配名称）
        $testResults = $repository->searchRoles('Test');
        $this->assertGreaterThanOrEqual(3, count($testResults));

        // 测试限制结果数量
        $limitedResults = $repository->searchRoles('Test', 2);
        $this->assertLessThanOrEqual(2, count($limitedResults));

        // 搜索不存在的内容
        $noResults = $repository->searchRoles('NONEXISTENT_SEARCH_TERM');
        $this->assertCount(0, $noResults);
    }

    public function testFindRootRoles(): void
    {
        $repository = $this->getRepository();

        // 创建根角色
        $rootRole1 = $this->createNewEntity();
        $rootRole1->setParentRoleId(null);
        $rootRole1->setHierarchyLevel(0);
        $repository->save($rootRole1, false);

        $rootRole2 = $this->createNewEntity();
        $rootRole2->setParentRoleId(null);
        $rootRole2->setHierarchyLevel(0);
        $repository->save($rootRole2, true);

        // 创建非根角色
        $childRole = $this->createNewEntity();
        $childRole->setParentRoleId($rootRole1->getId());
        $childRole->setHierarchyLevel(1);
        $repository->save($childRole, true);

        // 查找根角色
        $rootRoles = $repository->findRootRoles();
        $this->assertGreaterThanOrEqual(2, count($rootRoles));

        // 验证返回的都是根角色
        foreach ($rootRoles as $role) {
            $this->assertNull($role->getParentRoleId());
        }

        // 验证我们创建的根角色在结果中
        $rootCodes = array_map(fn ($role) => $role->getCode(), $rootRoles);
        $this->assertContains($rootRole1->getCode(), $rootCodes);
        $this->assertContains($rootRole2->getCode(), $rootCodes);
        $this->assertNotContains($childRole->getCode(), $rootCodes);
    }

    public function testFindRoleHierarchy(): void
    {
        $repository = $this->getRepository();

        // 创建不同层级的角色
        $level0Role = $this->createNewEntity();
        $level0Role->setParentRoleId(null);
        $level0Role->setHierarchyLevel(0);
        $repository->save($level0Role, false);

        $level1Role = $this->createNewEntity();
        $level1Role->setParentRoleId($level0Role->getId());
        $level1Role->setHierarchyLevel(1);
        $repository->save($level1Role, false);

        $level2Role = $this->createNewEntity();
        $level2Role->setParentRoleId($level1Role->getId());
        $level2Role->setHierarchyLevel(2);
        $repository->save($level2Role, true);

        // 查找所有层级
        $allHierarchy = $repository->findRoleHierarchy();
        $this->assertGreaterThanOrEqual(3, count($allHierarchy));

        // 验证排序正确（按层级和名称排序）
        $prevLevel = -1;
        foreach ($allHierarchy as $role) {
            $currentLevel = $role->getHierarchyLevel() ?? 0;
            $this->assertGreaterThanOrEqual($prevLevel, $currentLevel);
            if ($currentLevel > $prevLevel) {
                $prevLevel = $currentLevel;
            }
        }

        // 查找限制层级
        $limitedHierarchy = $repository->findRoleHierarchy(1);
        foreach ($limitedHierarchy as $role) {
            $this->assertLessThanOrEqual(1, $role->getHierarchyLevel() ?? 0);
        }

        // 验证我们创建的角色在结果中
        $allCodes = array_map(fn ($role) => $role->getCode(), $allHierarchy);
        $this->assertContains($level0Role->getCode(), $allCodes);
        $this->assertContains($level1Role->getCode(), $allCodes);
        $this->assertContains($level2Role->getCode(), $allCodes);
    }

    public function testSaveAndRemove(): void
    {
        $repository = $this->getRepository();

        // 测试保存
        $role = $this->createNewEntity();
        $code = $role->getCode();
        $repository->save($role, true);

        $saved = $repository->findOneByCode($code);
        $this->assertNotNull($saved);
        $this->assertEquals($code, $saved->getCode());

        // 测试不立即刷新的保存
        $role2 = $this->createNewEntity();
        $code2 = $role2->getCode();
        $repository->save($role2, false);

        // 刷新前应该找不到
        $notYetSaved = $repository->findOneByCode($code2);
        $this->assertNull($notYetSaved);

        // 手动刷新
        self::getEntityManager()->flush();
        // 清理实体管理器缓存后重新查询
        self::getEntityManager()->clear();
        $nowSaved = $repository->findOneByCode($code2);
        $this->assertNotNull($nowSaved);

        // 测试删除 - 需要重新获取实体
        self::getEntityManager()->clear();
        $savedForRemove = $repository->findOneByCode($code);
        $this->assertNotNull($savedForRemove);
        $repository->remove($savedForRemove, true);
        self::getEntityManager()->clear();
        $removed = $repository->findOneByCode($code);
        $this->assertNull($removed);

        // 测试不立即刷新的删除
        self::getEntityManager()->clear();
        $nowSavedForRemove = $repository->findOneByCode($code2);
        $this->assertNotNull($nowSavedForRemove);
        $repository->remove($nowSavedForRemove, false);

        // 删除前应该还能找到
        $notYetRemoved = $repository->findOneByCode($code2);
        $this->assertNotNull($notYetRemoved);

        // 手动刷新后应该删除
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();
        $nowRemoved = $repository->findOneByCode($code2);
        $this->assertNull($nowRemoved);
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();

        // 创建并保存一个角色
        $role = $this->createNewEntity();
        $code = $role->getCode();
        $repository->save($role, true);

        // 确认角色已保存
        $saved = $repository->findOneByCode($code);
        $this->assertNotNull($saved);

        // 测试删除
        $repository->remove($saved, true);

        // 确认角色已删除
        $removed = $repository->findOneByCode($code);
        $this->assertNull($removed);
    }

    /**
     * @return RoleRepository
     */
    protected function getRepository(): \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository
    {
        return $this->roleRepository;
    }
}
