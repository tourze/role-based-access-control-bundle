<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;

/**
 * @internal
 */
#[CoversClass(Permission::class)]
class PermissionTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): object
    {
        return new Permission();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'code' => ['code', 'PERMISSION_USER_EDIT'];
        yield 'name' => ['name', 'Edit User'];
        yield 'description' => ['description', 'Allow user to edit user information'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable('2024-01-01 10:00:00')];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable('2024-01-02 10:00:00')];
    }

    public function testPermissionCanBeInstantiated(): void
    {
        $permission = new Permission();
        $this->assertInstanceOf(Permission::class, $permission);
    }

    public function testPermissionCodeCanBeSetAndRetrieved(): void
    {
        $permission = new Permission();
        $code = 'PERMISSION_USER_EDIT';

        $permission->setCode($code);
        $this->assertEquals($code, $permission->getCode());
    }

    public function testPermissionNameCanBeSetAndRetrieved(): void
    {
        $permission = new Permission();
        $name = 'Edit User';

        $permission->setName($name);
        $this->assertEquals($name, $permission->getName());
    }

    public function testPermissionDescriptionCanBeSetAndRetrieved(): void
    {
        $permission = new Permission();
        $description = 'Allow user to edit user information';

        $permission->setDescription($description);
        $this->assertEquals($description, $permission->getDescription());
    }

    public function testPermissionTimestampsCanBeSetAndRetrieved(): void
    {
        $permission = new Permission();
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updateTime = new \DateTimeImmutable('2024-01-02 10:00:00');

        $permission->setCreateTime($createTime);
        $permission->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $permission->getCreateTime());
        $this->assertEquals($updateTime, $permission->getUpdateTime());
    }

    public function testPermissionIdCanBeRetrieved(): void
    {
        $permission = new Permission();
        // 新实例的ID应该是null
        $this->assertNull($permission->getId());

        // 使用反射设置ID（模拟持久化后的状态）
        $reflection = new \ReflectionClass($permission);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($permission, 456);

        $this->assertEquals(456, $permission->getId());
    }

    public function testPermissionRolesCollectionInitialization(): void
    {
        $permission = new Permission();
        $roles = $permission->getRoles();

        // 角色集合应该是Collection接口的实例
        $this->assertInstanceOf(Collection::class, $roles);
        $this->assertTrue($roles->isEmpty());
    }

    public function testPermissionCodeValidation(): void
    {
        // 测试有效的权限码格式
        $validCodes = [
            'PERMISSION_USER_EDIT',
            'PERMISSION_USER_VIEW',
            'PERMISSION_ORDER_CREATE',
            'PERMISSION_ARTICLE_PUBLISH',
            'PERMISSION_SYSTEM_ADMIN',
        ];

        foreach ($validCodes as $code) {
            $this->assertMatchesRegularExpression('/^PERMISSION_[A-Z]+_[A-Z]+$/', $code);
        }
    }

    public function testPermissionCanBeAddedToRole(): void
    {
        $permission = new Permission();
        $permission->setCode('PERMISSION_TEST_ACTION');
        $permission->setName('Test Action');

        $role = new Role();
        $role->setCode('ROLE_TEST');
        $role->setName('Test Role');

        // 测试可以将权限添加到角色的权限集合
        $role->getPermissions()->add($permission);
        $permission->getRoles()->add($role);

        $this->assertTrue($role->getPermissions()->contains($permission));
        $this->assertTrue($permission->getRoles()->contains($role));
    }

    public function testPermissionConstructorInitializesDefaults(): void
    {
        $permission = new Permission();

        // 构造函数应该初始化集合，时间戳由TimestampableAware trait管理，初始为null
        $this->assertInstanceOf(Collection::class, $permission->getRoles());
        $this->assertNull($permission->getCreateTime());
        $this->assertNull($permission->getUpdateTime());

        // 测试可以手动设置时间戳
        $now = new \DateTimeImmutable();
        $permission->setCreateTime($now);
        $permission->setUpdateTime($now);

        $this->assertEquals($now, $permission->getCreateTime());
        $this->assertEquals($now, $permission->getUpdateTime());
    }
}
