<?php

namespace Tourze\RoleBasedAccessControlBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RoleBasedAccessControlBundle\Exception\PermissionNotFoundException;

/**
 * @internal
 */
#[CoversClass(PermissionNotFoundException::class)]
class PermissionNotFoundExceptionTest extends AbstractExceptionTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testExceptionCanBeInstantiated(): void
    {
        $exception = new PermissionNotFoundException('Test message');
        $this->assertInstanceOf(PermissionNotFoundException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testStaticFactoryMethod(): void
    {
        $permissionCode = 'PERMISSION_TEST';
        $exception = PermissionNotFoundException::forPermissionCode($permissionCode);

        $this->assertEquals($permissionCode, $exception->getPermissionCode());
        $this->assertStringContainsString($permissionCode, $exception->getMessage());
    }

    public function testExceptionExtendsInvalidArgumentException(): void
    {
        $exception = new PermissionNotFoundException('Test');
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}
