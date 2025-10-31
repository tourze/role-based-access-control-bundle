<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;

/**
 * @internal
 */
#[CoversClass(Role::class)]
class RoleTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new Role();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'code' => ['code', 'ROLE_ADMIN'];
        yield 'name' => ['name', 'Administrator'];
        yield 'description' => ['description', 'Full system administrator role'];
        yield 'parentRoleId' => ['parentRoleId', 123];
        yield 'hierarchyLevel' => ['hierarchyLevel', 2];
        yield 'createTime' => ['createTime', new \DateTimeImmutable('2024-01-01 10:00:00')];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable('2024-01-02 10:00:00')];
    }

    public function testRoleCanBeInstantiated(): void
    {
        $role = new Role();
        $this->assertInstanceOf(Role::class, $role);
    }

    public function testRoleCodeCanBeSetAndRetrieved(): void
    {
        $role = new Role();
        $code = 'ROLE_ADMIN';

        $role->setCode($code);
        $this->assertEquals($code, $role->getCode());
    }

    public function testRoleNameCanBeSetAndRetrieved(): void
    {
        $role = new Role();
        $name = 'Administrator';

        $role->setName($name);
        $this->assertEquals($name, $role->getName());
    }

    public function testRoleDescriptionCanBeSetAndRetrieved(): void
    {
        $role = new Role();
        $description = 'Full system administrator role';

        $role->setDescription($description);
        $this->assertEquals($description, $role->getDescription());
    }

    public function testRoleTimestampsCanBeSetAndRetrieved(): void
    {
        $role = new Role();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updateTime = new \DateTimeImmutable('2024-01-02 10:00:00');

        $role->setCreateTime($createTime);
        $role->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $role->getCreateTime());
        $this->assertEquals($updateTime, $role->getUpdateTime());
    }

    public function testRoleParentRoleIdCanBeSetAndRetrieved(): void
    {
        $role = new Role();
        $parentRoleId = 123;

        $role->setParentRoleId($parentRoleId);
        $this->assertEquals($parentRoleId, $role->getParentRoleId());

        // 测试null值
        $role->setParentRoleId(null);
        $this->assertNull($role->getParentRoleId());
    }

    public function testRoleHierarchyLevelCanBeSetAndRetrieved(): void
    {
        $role = new Role();
        $hierarchyLevel = 2;

        $role->setHierarchyLevel($hierarchyLevel);
        $this->assertEquals($hierarchyLevel, $role->getHierarchyLevel());

        // 测试null值
        $role->setHierarchyLevel(null);
        $this->assertNull($role->getHierarchyLevel());
    }

    public function testRoleIdCanBeRetrieved(): void
    {
        $role = new Role();
        // 新实例的ID应该是null
        $this->assertNull($role->getId());

        // 使用反射设置ID（模拟持久化后的状态）
        $reflection = new \ReflectionClass($role);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($role, 123);

        $this->assertEquals(123, $role->getId());
    }

    public function testRolePermissionsCollectionInitialization(): void
    {
        $role = new Role();
        $permissions = $role->getPermissions();

        // 权限集合应该是Collection接口的实例
        $this->assertInstanceOf(Collection::class, $permissions);
        $this->assertTrue($permissions->isEmpty());
    }

    public function testRoleUserRolesCollectionInitialization(): void
    {
        $role = new Role();
        $userRoles = $role->getUserRoles();

        // 用户角色集合应该是Collection接口的实例
        $this->assertInstanceOf(Collection::class, $userRoles);
        $this->assertTrue($userRoles->isEmpty());
    }
}
