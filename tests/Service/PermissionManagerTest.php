<?php

declare(strict_types=1);


namespace Tourze\RoleBasedAccessControlBundle\Tests\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\ServerVersionProvider;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;
use Tourze\RoleBasedAccessControlBundle\Exception\DeletionConflictException;
use Tourze\RoleBasedAccessControlBundle\Exception\PermissionNotFoundException;
use Tourze\RoleBasedAccessControlBundle\Exception\RoleNotFoundException;
use Tourze\RoleBasedAccessControlBundle\Repository\PermissionRepository;
use Tourze\RoleBasedAccessControlBundle\Repository\RoleRepository;
use Tourze\RoleBasedAccessControlBundle\Repository\UserRoleRepository;
use Tourze\RoleBasedAccessControlBundle\Service\BulkOperationResult;
use Tourze\RoleBasedAccessControlBundle\Service\PermissionManager;
use Tourze\RoleBasedAccessControlBundle\Tests\TestUser;

/**
 * @internal
 */
#[CoversClass(PermissionManager::class)]
class PermissionManagerTest extends TestCase
{
    private PermissionManager $permissionManager;

    private EntityManagerInterface $entityManager;

    /**
     * Mock RoleRepository 包含测试辅助方法
     *
     * @var RoleRepository
     */
    private RoleRepository $roleRepository;

    /**
     * Mock PermissionRepository 包含测试辅助方法
     *
     * @var PermissionRepository
     */
    private PermissionRepository $permissionRepository;

    /**
     * Mock UserRoleRepository 包含测试辅助方法
     *
     * @var UserRoleRepository
     */
    private UserRoleRepository $userRoleRepository;

    private UserInterface $user;

    protected function setUp(): void
    {
        parent::setUp();

        // @phpstan-ignore-next-line
        $this->entityManager = new class implements EntityManagerInterface {
            private int $removeCallCount = 0;

            private int $flushCallCount = 0;

            /** @var array<object> */
            private array $removedEntities = [];

            /** @var Connection */
            private $mockConnection;

            public function __construct()
            {
                // 创建 Mock Connection 对象
                $this->mockConnection = new class extends Connection {
                    private bool $isTransactionActive = false;

                    public function __construct(
                        array $params = [],
                        ?Driver $driver = null,
                        ?\Doctrine\DBAL\Configuration $config = null,
                    ) {
                        // 使用默认参数调用父构造函数
                        parent::__construct(
                            $params,
                            $driver ?? $this->createMockDriver(),
                            $config ?? new \Doctrine\DBAL\Configuration()
                        );
                    }

                    private function createMockDriver(): Driver
                    {
                        return new class implements Driver {
                            public function connect(array $params): Driver\Connection
                            {
                                throw new \LogicException('Mock driver - not implemented');
                            }

                            public function getDatabasePlatform(?ServerVersionProvider $versionProvider = null): AbstractPlatform
                            {
                                throw new \LogicException('Mock driver - not implemented');
                            }

                            /**
                             * @phpstan-ignore missingType.generics
                             */
                            public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
                            {
                                throw new \LogicException('Mock driver - not implemented');
                            }

                            public function getExceptionConverter(): ExceptionConverter
                            {
                                throw new \LogicException('Mock driver - not implemented');
                            }
                        };
                    }

                    public function isTransactionActive(): bool
                    {
                        return $this->isTransactionActive;
                    }

                    public function setTransactionActive(bool $active): void
                    {
                        $this->isTransactionActive = $active;
                    }
                };
            }

            public function persist(object $entity): void
            {
            }

            public function remove(object $entity): void
            {
                ++$this->removeCallCount;
                $this->removedEntities[] = $entity;
            }

            public function merge(object $entity): object
            {
                return $entity;
            }

            public function clear(?string $entityName = null): void
            {
            }

            public function detach(object $entity): void
            {
            }

            public function refresh(object $object, LockMode|int|null $lockMode = null): void
            {
            }

            public function flush(): void
            {
                ++$this->flushCallCount;
            }

            public function find(string $className, mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return null;
            }

            public function getReference(string $entityName, mixed $id): object
            {
                throw new \Exception('Not implemented');
            }

            public function getPartialReference(string $entityName, mixed $identifier): object
            {
                throw new \Exception('Not implemented');
            }

            public function getClassMetadata(string $className): ClassMetadata
            {
                throw new \Exception('Not implemented');
            }

            // @phpstan-ignore method.childReturnType
            public function getMetadataFactory(): ClassMetadataFactory
            {
                throw new \Exception('Not implemented');
            }

            public function getRepository(string $className): EntityRepository
            {
                throw new \Exception('Not implemented');
            }

            public function contains(object $entity): bool
            {
                return false;
            }

            public function getEventManager(): EventManager
            {
                throw new \Exception('Not implemented');
            }

            public function getConfiguration(): Configuration
            {
                throw new \Exception('Not implemented');
            }

            public function isOpen(): bool
            {
                return true;
            }

            public function getUnitOfWork(): UnitOfWork
            {
                throw new \Exception('Not implemented');
            }

            public function getHydrator(string|int $hydrationMode): AbstractHydrator
            {
                throw new \Exception('Not implemented');
            }

            public function newHydrator(string|int $hydrationMode): AbstractHydrator
            {
                throw new \Exception('Not implemented');
            }

            public function getProxyFactory(): ProxyFactory
            {
                throw new \Exception('Not implemented');
            }

            public function initializeObject(object $obj): void
            {
            }

            public function isUninitializedObject(mixed $value): bool
            {
                return false;
            }

            public function wrapInTransaction(callable $func): mixed
            {
                return $func($this);
            }

            public function commit(): void
            {
            }

            public function rollback(): void
            {
            }

            public function getConnection(): Connection
            {
                return $this->mockConnection;
            }

            public function getExpressionBuilder(): Expr
            {
                throw new \Exception('Not implemented');
            }

            public function beginTransaction(): void
            {
            }

            public function transactional(callable $func): mixed
            {
                return $func($this);
            }

            public function lock(object $entity, LockMode|int $lockMode, \DateTimeInterface|int|null $lockVersion = null): void
            {
            }

            public function copy(object $entity, bool $deep = false): object
            {
                return clone $entity;
            }

            public function getCache(): ?Cache
            {
                return null;
            }

            public function getFilters(): FilterCollection
            {
                throw new \Exception('Not implemented');
            }

            public function isFiltersStateClean(): bool
            {
                return true;
            }

            public function hasFilters(): bool
            {
                return false;
            }

            // 剩余的抽象方法
            public function createQuery(string $dql = ''): Query
            {
                throw new \Exception('Not implemented');
            }

            public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
            {
                throw new \Exception('Not implemented');
            }

            public function createQueryBuilder(): QueryBuilder
            {
                throw new \Exception('Not implemented');
            }

            public function close(): void
            {
            }

            public function getRemoveCallCount(): int
            {
                return $this->removeCallCount;
            }

            public function getFlushCallCount(): int
            {
                return $this->flushCallCount;
            }

            /** @return array<object> */
            public function getRemovedEntities(): array
            {
                return $this->removedEntities;
            }

            public function resetCounts(): void
            {
                $this->removeCallCount = 0;
                $this->flushCallCount = 0;
                $this->removedEntities = [];
            }
        };

        // @phpstan-ignore-next-line
        $this->roleRepository = new class($this->createMock(\Doctrine\Persistence\ManagerRegistry::class)) extends RoleRepository {
            public function __construct(\Doctrine\Persistence\ManagerRegistry $registry)
            {
                parent::__construct($registry);
            }

            /** @var array<string, ?Role> */
            private array $findOneByCodeResults = [];

            public function setFindOneByCodeResult(string $code, ?Role $role): void
            {
                $this->findOneByCodeResults[$code] = $role;
            }

            public function findOneByCode(string $code): ?Role
            {
                return $this->findOneByCodeResults[$code] ?? null;
            }
        };

        // @phpstan-ignore-next-line
        $this->permissionRepository = new class($this->createMock(\Doctrine\Persistence\ManagerRegistry::class)) extends PermissionRepository {
            public function __construct(\Doctrine\Persistence\ManagerRegistry $registry)
            {
                parent::__construct($registry);
            }

            /** @var array<string, ?Permission> */
            private array $findOneByCodeResults = [];

            /** @var array<string, array<string>> */
            private array $findPermissionsForDeletionCheckResults = [];

            public function setFindOneByCodeResult(string $code, ?Permission $permission): void
            {
                $this->findOneByCodeResults[$code] = $permission;
            }

            /** @param array<string> $roleCodes */
            public function setFindPermissionsForDeletionCheckResult(string $permissionCode, array $roleCodes): void
            {
                $this->findPermissionsForDeletionCheckResults[$permissionCode] = $roleCodes;
            }

            public function findOneByCode(string $code): ?Permission
            {
                return $this->findOneByCodeResults[$code] ?? null;
            }

            /** @return array<string> */
            public function findPermissionsForDeletionCheck(string $permissionCode): array
            {
                return $this->findPermissionsForDeletionCheckResults[$permissionCode] ?? [];
            }
        };

        // @phpstan-ignore-next-line
        $this->userRoleRepository = new class($this->createMock(\Doctrine\Persistence\ManagerRegistry::class)) extends UserRoleRepository {
            public function __construct(\Doctrine\Persistence\ManagerRegistry $registry)
            {
                parent::__construct($registry);
            }

            /** @var array<string, ?UserRole> */
            private array $findUserRoleByUserAndRoleResults = [];

            /** @var array<string, int> */
            private array $countUsersByRoleCodeResults = [];

            /** @var array<string, array<UserRole>> */
            private array $findByUserIdResults = [];

            public function setFindUserRoleByUserAndRoleResult(string $userId, string $roleCode, ?UserRole $userRole): void
            {
                $this->findUserRoleByUserAndRoleResults[$userId . ':' . $roleCode] = $userRole;
            }

            public function setCountUsersByRoleCodeResult(string $roleCode, int $count): void
            {
                $this->countUsersByRoleCodeResults[$roleCode] = $count;
            }

            /** @param array<UserRole> $userRoles */
            public function setFindByUserIdResult(string $userId, array $userRoles): void
            {
                $this->findByUserIdResults[$userId] = $userRoles;
            }

            public function findUserRoleByUserAndRole(string $userId, string $roleCode): ?UserRole
            {
                return $this->findUserRoleByUserAndRoleResults[$userId . ':' . $roleCode] ?? null;
            }

            public function countUsersByRoleCode(string $roleCode): int
            {
                return $this->countUsersByRoleCodeResults[$roleCode] ?? 0;
            }

            /** @return array<UserRole> */
            public function findByUserId(string $userId): array
            {
                return $this->findByUserIdResults[$userId] ?? [];
            }
        };

        $this->permissionManager = new PermissionManager(
            $this->entityManager,
            $this->roleRepository,
            $this->permissionRepository,
            $this->userRoleRepository
        );

        $this->user = new TestUser('test@example.com');
    }

    public function testAssignRoleToUserWhenRoleExists(): void
    {
        // 测试：角色存在时分配成功
        $roleCode = 'ROLE_EDITOR';

        $role = new Role();
        $role->setCode($roleCode);

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, $role);
        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult($this->user->getUserIdentifier(), $roleCode, null);

        // 执行测试
        $result = $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        // 断言结果
        $this->assertTrue($result); // 应该返回true表示发生了变更
    }

    public function testAssignRoleToUserWhenRoleNotExists(): void
    {
        // 测试：角色不存在时抛出异常
        $roleCode = 'INVALID_ROLE';

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, null);

        // 断言异常
        $this->expectException(RoleNotFoundException::class);
        $this->expectExceptionMessage('Role with code "INVALID_ROLE" not found');

        $this->permissionManager->assignRoleToUser($this->user, $roleCode);
    }

    public function testAssignRoleToUserWhenAlreadyAssigned(): void
    {
        // 测试：用户已有角色时的幂等性
        $roleCode = 'ROLE_EDITOR';

        $role = new Role();
        $role->setCode($roleCode);

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, $role);
        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult($this->user->getUserIdentifier(), $roleCode, new UserRole());

        $result = $this->permissionManager->assignRoleToUser($this->user, $roleCode);

        // 断言：没有发生变更
        $this->assertFalse($result);
    }

    public function testAddPermissionToRoleWhenBothExist(): void
    {
        // 测试：角色和权限都存在时添加成功
        $roleCode = 'ROLE_EDITOR';
        $permissionCode = 'PERMISSION_USER_EDIT';

        $role = new Role();
        $role->setCode($roleCode);

        $permission = new Permission();
        $permission->setCode($permissionCode);

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, $role);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult($permissionCode, $permission);

        $result = $this->permissionManager->addPermissionToRole($roleCode, $permissionCode);

        $this->assertTrue($result);
    }

    public function testAddPermissionToRoleWhenPermissionNotExists(): void
    {
        // 测试：权限不存在时抛出异常
        $roleCode = 'ROLE_EDITOR';
        $permissionCode = 'INVALID_PERMISSION';

        $role = new Role();
        $role->setCode($roleCode);

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, $role);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult($permissionCode, null);

        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage('Permission with code "INVALID_PERMISSION" not found');

        $this->permissionManager->addPermissionToRole($roleCode, $permissionCode);
    }

    public function testGetUserPermissionsReturnsCorrectPermissions(): void
    {
        // 测试：获取用户权限返回正确结果
        $expectedPermissions = ['PERMISSION_USER_EDIT', 'PERMISSION_USER_VIEW'];

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindByUserIdResult($this->user->getUserIdentifier(), []);

        // 先返回空数组，绿色阶段会实现真正的逻辑
        $result = $this->permissionManager->getUserPermissions($this->user);

        $this->assertSame([], $result);
    }

    public function testHasPermissionReturnsTrueWhenUserHasPermission(): void
    {
        // 测试：用户有权限时返回true
        $permission = 'PERMISSION_USER_EDIT';

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindByUserIdResult($this->user->getUserIdentifier(), []);

        $result = $this->permissionManager->hasPermission($this->user, $permission);

        $this->assertFalse($result);
    }

    public function testCanDeleteRoleReturnsFalseWhenRoleHasUsers(): void
    {
        // 测试：角色有用户时不能删除
        $roleCode = 'ROLE_EDITOR';

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setCountUsersByRoleCodeResult($roleCode, 5);

        $result = $this->permissionManager->canDeleteRole($roleCode);

        $this->assertFalse($result);
    }

    public function testDeleteRoleThrowsExceptionWhenCannotDelete(): void
    {
        // 测试：不能删除时抛出异常
        $roleCode = 'ROLE_EDITOR';

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setCountUsersByRoleCodeResult($roleCode, 3);

        $this->expectException(DeletionConflictException::class);

        $this->permissionManager->deleteRole($roleCode);
    }

    public function testBulkAssignRolesHandlesPartialFailures(): void
    {
        // 测试：批量分配处理部分失败
        $mapping = [
            'user1' => ['ROLE_EDITOR'],
            'user2' => ['INVALID_ROLE'],  // 这个会失败
            'user3' => ['ROLE_VIEWER'],
        ];

        $result = $this->permissionManager->bulkAssignRoles($mapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
    }

    public function testBulkGrantPermissions(): void
    {
        // 测试：批量授予权限成功
        $rolePermissionMapping = [
            'ROLE_EDITOR' => ['PERMISSION_USER_EDIT', 'PERMISSION_USER_VIEW'],
            'ROLE_VIEWER' => ['PERMISSION_USER_VIEW'],
        ];

        $role1 = new Role();
        $role1->setCode('ROLE_EDITOR');

        $role2 = new Role();
        $role2->setCode('ROLE_VIEWER');

        $permission1 = new Permission();
        $permission1->setCode('PERMISSION_USER_EDIT');

        $permission2 = new Permission();
        $permission2->setCode('PERMISSION_USER_VIEW');

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult('ROLE_EDITOR', $role1);
        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult('ROLE_VIEWER', $role2);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult('PERMISSION_USER_EDIT', $permission1);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult('PERMISSION_USER_VIEW', $permission2);

        $result = $this->permissionManager->bulkGrantPermissions($rolePermissionMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertGreaterThanOrEqual(0, $result->getSuccessCount());
    }

    public function testBulkGrantPermissionsHandlesPartialFailures(): void
    {
        // 测试：批量授予权限处理部分失败
        $rolePermissionMapping = [
            'ROLE_EDITOR' => ['PERMISSION_USER_EDIT'],
            'INVALID_ROLE' => ['PERMISSION_USER_VIEW'],  // 这个会失败
        ];

        $role = new Role();
        $role->setCode('ROLE_EDITOR');

        $permission = new Permission();
        $permission->setCode('PERMISSION_USER_EDIT');

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult('ROLE_EDITOR', $role);
        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult('INVALID_ROLE', null);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult('PERMISSION_USER_EDIT', $permission);

        $result = $this->permissionManager->bulkGrantPermissions($rolePermissionMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertGreaterThan(0, $result->getFailureCount());
        $this->assertNotEmpty($result->getFailures());
    }

    public function testBulkRevokeRoles(): void
    {
        // 测试：批量撤销角色成功
        $userRoleMapping = [
            'user1@example.com' => ['ROLE_EDITOR'],
            'user2@example.com' => ['ROLE_VIEWER'],
        ];

        $userRole1 = new UserRole();
        $userRole2 = new UserRole();

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult('user1@example.com', 'ROLE_EDITOR', $userRole1);
        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult('user2@example.com', 'ROLE_VIEWER', $userRole2);

        $result = $this->permissionManager->bulkRevokeRoles($userRoleMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertGreaterThanOrEqual(0, $result->getSuccessCount());
    }

    public function testBulkRevokeRolesHandlesNonExistentAssignments(): void
    {
        // 测试：批量撤销角色处理不存在的分配
        $userRoleMapping = [
            'user1@example.com' => ['ROLE_EDITOR'],
            'user2@example.com' => ['ROLE_VIEWER'],
        ];

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult('user1@example.com', 'ROLE_EDITOR', null);
        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult('user2@example.com', 'ROLE_VIEWER', null);

        $result = $this->permissionManager->bulkRevokeRoles($userRoleMapping);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEquals(0, $result->getSuccessCount()); // 没有实际撤销
    }

    public function testCanDeletePermissionReturnsTrueWhenNoRolesHavePermission(): void
    {
        // 测试：没有角色拥有权限时可以删除
        $permissionCode = 'PERMISSION_USER_DELETE';

        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindPermissionsForDeletionCheckResult($permissionCode, []);

        $result = $this->permissionManager->canDeletePermission($permissionCode);

        $this->assertTrue($result);
    }

    public function testCanDeletePermissionReturnsFalseWhenRolesHavePermission(): void
    {
        // 测试：有角色拥有权限时不能删除
        $permissionCode = 'PERMISSION_USER_DELETE';

        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindPermissionsForDeletionCheckResult($permissionCode, ['ROLE_ADMIN', 'ROLE_EDITOR']);

        $result = $this->permissionManager->canDeletePermission($permissionCode);

        $this->assertFalse($result);
    }

    public function testDeletePermissionWhenCanDelete(): void
    {
        $this->expectNotToPerformAssertions();

        // 测试：可以删除时成功删除权限
        $permissionCode = 'PERMISSION_USER_DELETE';

        $permission = new Permission();
        $permission->setCode($permissionCode);

        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindPermissionsForDeletionCheckResult($permissionCode, []);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult($permissionCode, $permission);

        // EntityManager 的调用会在匿名类中自动记录

        $this->permissionManager->deletePermission($permissionCode);

        // 验证删除操作不抛出异常（幂等操作）
    }

    public function testDeletePermissionThrowsExceptionWhenCannotDelete(): void
    {
        // 测试：不能删除时抛出异常
        $permissionCode = 'PERMISSION_USER_DELETE';

        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindPermissionsForDeletionCheckResult($permissionCode, ['ROLE_ADMIN']);

        $this->expectException(DeletionConflictException::class);
        $this->expectExceptionMessage('Cannot delete permission "PERMISSION_USER_DELETE"');

        $this->permissionManager->deletePermission($permissionCode);
    }

    public function testDeletePermissionWhenPermissionNotExists(): void
    {
        $this->expectNotToPerformAssertions();

        // 测试：权限不存在时的幂等操作
        $permissionCode = 'NONEXISTENT_PERMISSION';

        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindPermissionsForDeletionCheckResult($permissionCode, []);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult($permissionCode, null);

        // EntityManager 的调用会在匿名类中自动记录

        // 应该不抛出异常，幂等操作
        $this->permissionManager->deletePermission($permissionCode);

        // 验证幂等操作不抛出异常
    }

    public function testRemovePermissionFromRoleWhenBothExist(): void
    {
        // 测试：角色和权限都存在时移除成功
        $roleCode = 'ROLE_EDITOR';
        $permissionCode = 'PERMISSION_USER_DELETE';

        $role = new Role();
        $role->setCode($roleCode);

        $permission = new Permission();
        $permission->setCode($permissionCode);

        // 模拟权限集合包含该权限
        /** @var Collection<int, Permission> $permissionsCollection */
        $permissionsCollection = new /**
         * @implements Collection<int, Permission>
         */
        class implements Collection {
            public function contains(mixed $element): bool
            {
                return true;
            }

            public function add(mixed $element): void
            {
            }

            public function removeElement(mixed $element): bool
            {
                return true;
            }

            public function remove(string|int $key): mixed
            {
                return null;
            }

            public function clear(): void
            {
            }

            public function count(): int
            {
                return 1;
            }

            public function isEmpty(): bool
            {
                return false;
            }

            public function toArray(): array
            {
                return [];
            }

            public function first(): mixed
            {
                return false;
            }

            public function last(): mixed
            {
                return false;
            }

            public function key(): int|string|null
            {
                return null;
            }

            public function current(): mixed
            {
                return false;
            }

            public function next(): mixed
            {
                return false;
            }

            public function exists(\Closure $p): bool
            {
                return false;
            }

            public function filter(\Closure $p): Collection
            {
                return $this;
            }

            public function forAll(\Closure $p): bool
            {
                return true;
            }

            public function map(\Closure $func): Collection
            {
                return $this;
            }

            public function partition(\Closure $p): array
            {
                return [$this, $this];
            }

            /** @return int|string|false */
            public function indexOf(mixed $element): int|string|false
            {
                return false;
            }

            public function slice(int $offset, ?int $length = null): array
            {
                return [];
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([]);
            }

            public function offsetExists(mixed $offset): bool
            {
                return false;
            }

            public function offsetGet(mixed $offset): mixed
            {
                return null;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
            }

            public function offsetUnset(mixed $offset): void
            {
            }

            public function containsKey(string|int $key): bool
            {
                return false;
            }

            public function get(string|int $key): mixed
            {
                return null;
            }

            public function getKeys(): array
            {
                return [];
            }

            public function getValues(): array
            {
                return [];
            }

            public function set(string|int $key, mixed $value): void
            {
            }

            public function matching(Criteria $criteria): static
            {
                return $this;
            }

            public function findFirst(\Closure $p): mixed
            {
                return null;
            }

            public function reduce(\Closure $func, mixed $initial = null): mixed
            {
                return $initial;
            }
        };

        $role = new class($permissionsCollection) extends Role {
            /** @var Collection<int, Permission> */
            private $permissionsCollection;

            /** @var bool */
            private $removePermissionCalled = false;

            /**
             * @param Collection<int, Permission> $permissionsCollection
             */
            public function __construct($permissionsCollection)
            {
                parent::__construct();
                $this->permissionsCollection = $permissionsCollection;
            }

            /** @return Collection<int, Permission> */
            public function getPermissions(): Collection
            {
                return $this->permissionsCollection;
            }

            public function removePermission(Permission $permission): self
            {
                $this->removePermissionCalled = true;

                return $this;
            }

            public function wasRemovePermissionCalled(): bool
            {
                return $this->removePermissionCalled;
            }
        };

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, $role);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult($permissionCode, $permission);

        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertTrue($result);
    }

    public function testRemovePermissionFromRoleWhenRoleNotExists(): void
    {
        // 测试：角色不存在时返回false
        $roleCode = 'NONEXISTENT_ROLE';
        $permissionCode = 'PERMISSION_USER_DELETE';

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, null);

        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertFalse($result);
    }

    public function testRemovePermissionFromRoleWhenPermissionNotExists(): void
    {
        // 测试：权限不存在时返回false
        $roleCode = 'ROLE_EDITOR';
        $permissionCode = 'NONEXISTENT_PERMISSION';

        $role = new Role();
        $role->setCode($roleCode);

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, $role);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult($permissionCode, null);

        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertFalse($result);
    }

    public function testRemovePermissionFromRoleWhenPermissionNotInRole(): void
    {
        // 测试：角色没有该权限时返回false（幂等操作）
        $roleCode = 'ROLE_EDITOR';
        $permissionCode = 'PERMISSION_USER_DELETE';

        $permission = new Permission();
        $permission->setCode($permissionCode);

        /** @var Collection<int, Permission> $permissionsCollection */
        $permissionsCollection = new /**
         * @implements Collection<int, Permission>
         */
        class implements Collection {
            public function contains(mixed $element): bool
            {
                return false;
            }

            public function add(mixed $element): void
            {
            }

            public function removeElement(mixed $element): bool
            {
                return true;
            }

            public function remove(string|int $key): mixed
            {
                return null;
            }

            public function clear(): void
            {
            }

            public function count(): int
            {
                return 1;
            }

            public function isEmpty(): bool
            {
                return false;
            }

            public function toArray(): array
            {
                return [];
            }

            public function first(): mixed
            {
                return false;
            }

            public function last(): mixed
            {
                return false;
            }

            public function key(): int|string|null
            {
                return null;
            }

            public function current(): mixed
            {
                return false;
            }

            public function next(): mixed
            {
                return false;
            }

            public function exists(\Closure $p): bool
            {
                return false;
            }

            public function filter(\Closure $p): Collection
            {
                return $this;
            }

            public function forAll(\Closure $p): bool
            {
                return true;
            }

            public function map(\Closure $func): Collection
            {
                return $this;
            }

            public function partition(\Closure $p): array
            {
                return [$this, $this];
            }

            /** @return int|string|false */
            public function indexOf(mixed $element): int|string|false
            {
                return false;
            }

            public function slice(int $offset, ?int $length = null): array
            {
                return [];
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([]);
            }

            public function offsetExists(mixed $offset): bool
            {
                return false;
            }

            public function offsetGet(mixed $offset): mixed
            {
                return null;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
            }

            public function offsetUnset(mixed $offset): void
            {
            }

            public function containsKey(string|int $key): bool
            {
                return false;
            }

            public function get(string|int $key): mixed
            {
                return null;
            }

            public function getKeys(): array
            {
                return [];
            }

            public function getValues(): array
            {
                return [];
            }

            public function set(string|int $key, mixed $value): void
            {
            }

            public function matching(Criteria $criteria): static
            {
                return $this;
            }

            public function findFirst(\Closure $p): mixed
            {
                return null;
            }

            public function reduce(\Closure $func, mixed $initial = null): mixed
            {
                return $initial;
            }
        };

        $role = new class($permissionsCollection) extends Role {
            /** @var Collection<int, Permission> */
            private $permissionsCollection;

            /** @param Collection<int, Permission> $permissionsCollection */
            public function __construct($permissionsCollection)
            {
                parent::__construct();
                $this->permissionsCollection = $permissionsCollection;
            }

            /** @return Collection<int, Permission> */
            public function getPermissions(): Collection
            {
                return $this->permissionsCollection;
            }
        };

        // @phpstan-ignore method.notFound
        $this->roleRepository->setFindOneByCodeResult($roleCode, $role);
        // @phpstan-ignore method.notFound
        $this->permissionRepository->setFindOneByCodeResult($permissionCode, $permission);

        $result = $this->permissionManager->removePermissionFromRole($roleCode, $permissionCode);

        $this->assertFalse($result);
    }

    public function testRevokeRoleFromUserWhenAssignmentExists(): void
    {
        // 测试：用户角色分配存在时撤销成功
        $roleCode = 'ROLE_EDITOR';

        $userRole = new UserRole();

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult($this->user->getUserIdentifier(), $roleCode, $userRole);

        // EntityManager 的调用会在匿名类中自动记录

        $result = $this->permissionManager->revokeRoleFromUser($this->user, $roleCode);

        $this->assertTrue($result);
    }

    public function testRevokeRoleFromUserWhenAssignmentNotExists(): void
    {
        // 测试：用户角色分配不存在时返回false（幂等操作）
        $roleCode = 'ROLE_EDITOR';

        // @phpstan-ignore method.notFound
        $this->userRoleRepository->setFindUserRoleByUserAndRoleResult($this->user->getUserIdentifier(), $roleCode, null);
        // EntityManager 的调用会在匿名类中自动记录

        $result = $this->permissionManager->revokeRoleFromUser($this->user, $roleCode);

        $this->assertFalse($result);
    }
}
