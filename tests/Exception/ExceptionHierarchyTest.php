<?php

namespace Tourze\RoleBasedAccessControlBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RoleBasedAccessControlBundle\Exception\DeletionConflictException;
use Tourze\RoleBasedAccessControlBundle\Exception\PermissionNotFoundException;
use Tourze\RoleBasedAccessControlBundle\Exception\RoleNotFoundException;

/**
 * @internal
 */
#[CoversClass(\InvalidArgumentException::class)]
class ExceptionHierarchyTest extends AbstractExceptionTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testRoleNotFoundExceptionExtendsInvalidArgumentException(): void
    {
        $exception = new RoleNotFoundException('Test role not found');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('Test role not found', $exception->getMessage());
    }

    public function testPermissionNotFoundExceptionExtendsInvalidArgumentException(): void
    {
        $exception = new PermissionNotFoundException('Test permission not found');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('Test permission not found', $exception->getMessage());
    }

    public function testDeletionConflictExceptionExtendsRuntimeException(): void
    {
        $exception = new DeletionConflictException('Cannot delete due to conflicts');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Cannot delete due to conflicts', $exception->getMessage());
    }

    public function testRoleNotFoundExceptionHasContextInformation(): void
    {
        $roleCode = 'ROLE_INVALID';
        $exception = RoleNotFoundException::forRoleCode($roleCode);

        $this->assertStringContainsString($roleCode, $exception->getMessage());
        $this->assertEquals($roleCode, $exception->getRoleCode());
    }

    public function testPermissionNotFoundExceptionHasContextInformation(): void
    {
        $permissionCode = 'PERMISSION_INVALID';
        $exception = PermissionNotFoundException::forPermissionCode($permissionCode);

        $this->assertStringContainsString($permissionCode, $exception->getMessage());
        $this->assertEquals($permissionCode, $exception->getPermissionCode());
    }

    public function testDeletionConflictExceptionHasAffectedEntities(): void
    {
        $affectedEntities = ['user1', 'user2', 'user3'];
        $exception = DeletionConflictException::forRoleDeletion('ROLE_EDITOR', $affectedEntities);

        $this->assertEquals($affectedEntities, $exception->getAffectedEntities());
        $this->assertEquals('ROLE_EDITOR', $exception->getEntityIdentifier());
    }
}
