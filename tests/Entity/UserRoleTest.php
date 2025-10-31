<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;
use Tourze\RoleBasedAccessControlBundle\Tests\TestUser;

/**
 * @internal
 */
#[CoversClass(UserRole::class)]
class UserRoleTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new UserRole();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $role = new Role();
        $role->setCode('ROLE_TEST');
        $role->setName('Test Role');

        yield 'userId' => ['userId', 'user123@example.com'];
        yield 'role' => ['role', $role];
        yield 'assignTime' => ['assignTime', new \DateTimeImmutable('2024-01-01 12:00:00')];
    }

    public function testUserRoleCanBeInstantiated(): void
    {
        $userRole = new UserRole();
        $this->assertInstanceOf(UserRole::class, $userRole);
    }

    public function testUserRoleCanSetAndGetUser(): void
    {
        $userRole = new UserRole();
        $user = new TestUser('test@example.com');

        $userRole->setUser($user);
        $this->assertSame($user, $userRole->getUser());
    }

    public function testUserRoleCanSetAndGetRole(): void
    {
        $userRole = new UserRole();
        $role = new Role();
        $role->setCode('ROLE_TEST');
        $role->setName('Test Role');

        $userRole->setRole($role);
        $this->assertSame($role, $userRole->getRole());
    }

    public function testUserRoleIdCanBeRetrieved(): void
    {
        $userRole = new UserRole();
        // 新实例的ID应该是null
        $this->assertNull($userRole->getId());

        // 使用反射设置ID（模拟持久化后的状态）
        $reflection = new \ReflectionClass($userRole);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($userRole, 789);

        $this->assertEquals(789, $userRole->getId());
    }

    public function testUserRoleAssignTimeIsInitialized(): void
    {
        $userRole = new UserRole();

        $this->assertInstanceOf(\DateTimeImmutable::class, $userRole->getAssignTime());

        // 时间应该接近现在
        $now = new \DateTimeImmutable();
        $timeDiff = abs($now->getTimestamp() - $userRole->getAssignTime()->getTimestamp());
        $this->assertLessThanOrEqual(1, $timeDiff);
    }

    public function testUserRoleAssignTimeCanBeSetAndRetrieved(): void
    {
        $userRole = new UserRole();
        $assignTime = new \DateTimeImmutable('2024-01-01 12:00:00');

        $userRole->setAssignTime($assignTime);
        $this->assertEquals($assignTime, $userRole->getAssignTime());
    }

    public function testUserRoleCompleteAssociation(): void
    {
        $userRole = new UserRole();
        $user = new TestUser('admin@example.com');
        $role = new Role();
        $role->setCode('ROLE_ADMIN');
        $role->setName('Administrator');

        $userRole->setUser($user);
        $userRole->setRole($role);

        // 验证关联设置正确
        $this->assertSame($user, $userRole->getUser());
        $this->assertSame($role, $userRole->getRole());
        $this->assertEquals('admin@example.com', $userRole->getUser()->getUserIdentifier());
        $this->assertEquals('ROLE_ADMIN', $userRole->getRole()->getCode());
    }

    public function testUserRoleWithUserIdString(): void
    {
        $userRole = new UserRole();

        // 测试与string用户ID的兼容性
        $user = new TestUser('user123@domain.com');
        $userRole->setUser($user);

        $retrievedUser = $userRole->getUser();
        $this->assertNotNull($retrievedUser);
        $this->assertEquals('user123@domain.com', $retrievedUser->getUserIdentifier());
        $this->assertSame($user, $userRole->getUser());
    }
}
