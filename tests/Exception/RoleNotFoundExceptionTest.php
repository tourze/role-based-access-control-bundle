<?php

namespace Tourze\RoleBasedAccessControlBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\RoleBasedAccessControlBundle\Exception\RoleNotFoundException;

/**
 * @internal
 */
#[CoversClass(RoleNotFoundException::class)]
class RoleNotFoundExceptionTest extends AbstractExceptionTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testExceptionCanBeInstantiated(): void
    {
        $exception = new RoleNotFoundException('Test message');
        $this->assertInstanceOf(RoleNotFoundException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testStaticFactoryMethod(): void
    {
        $roleCode = 'ROLE_TEST';
        $exception = RoleNotFoundException::forRoleCode($roleCode);

        $this->assertEquals($roleCode, $exception->getRoleCode());
        $this->assertStringContainsString($roleCode, $exception->getMessage());
    }

    public function testExceptionExtendsInvalidArgumentException(): void
    {
        $exception = new RoleNotFoundException('Test');
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}
