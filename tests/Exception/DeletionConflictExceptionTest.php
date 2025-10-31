<?php

namespace Tourze\RoleBasedAccessControlBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RoleBasedAccessControlBundle\Exception\DeletionConflictException;

/**
 * @internal
 */
#[CoversClass(DeletionConflictException::class)]
class DeletionConflictExceptionTest extends AbstractExceptionTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testExceptionCanBeInstantiated(): void
    {
        $exception = new DeletionConflictException('Test message');
        $this->assertInstanceOf(DeletionConflictException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testRoleDeletionFactoryMethod(): void
    {
        $roleCode = 'ROLE_TEST';
        $affectedEntities = ['user1', 'user2'];
        $exception = DeletionConflictException::forRoleDeletion($roleCode, $affectedEntities);

        $this->assertEquals($roleCode, $exception->getEntityIdentifier());
        $this->assertEquals($affectedEntities, $exception->getAffectedEntities());
        $this->assertStringContainsString($roleCode, $exception->getMessage());
        $this->assertStringContainsString('2 users', $exception->getMessage());
    }

    public function testPermissionDeletionFactoryMethod(): void
    {
        $permissionCode = 'PERMISSION_TEST';
        $affectedEntities = ['role1', 'role2', 'role3'];
        $exception = DeletionConflictException::forPermissionDeletion($permissionCode, $affectedEntities);

        $this->assertEquals($permissionCode, $exception->getEntityIdentifier());
        $this->assertEquals($affectedEntities, $exception->getAffectedEntities());
        $this->assertStringContainsString($permissionCode, $exception->getMessage());
        $this->assertStringContainsString('3 roles', $exception->getMessage());
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new DeletionConflictException('Test');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
