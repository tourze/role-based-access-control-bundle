<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoleBasedAccessControlBundle\Service\AuditLogger;
use Tourze\RoleBasedAccessControlBundle\Tests\TestUser;

/**
 * @internal
 */
#[CoversClass(AuditLogger::class)]
#[RunTestsInSeparateProcesses]
class AuditLoggerTest extends AbstractIntegrationTestCase
{
    private AuditLogger $auditLogger;

    protected function onSetUp(): void
    {
        $this->auditLogger = self::getService(AuditLogger::class);
    }

    public function testAuditLoggerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(AuditLogger::class, $this->auditLogger);
    }

    public function testLogPermissionChangeExecutesWithoutError(): void
    {
        $action = 'role.assigned';
        $data = [
            'user_id' => 'test@example.com',
            'role_code' => 'ROLE_EDITOR',
            'operation_id' => 'op_12345',
        ];

        // 测试方法调用不会抛出异常
        $this->expectNotToPerformAssertions();
        $this->auditLogger->logPermissionChange($action, $data);
    }

    public function testLogPermissionCheckExecutesWithoutError(): void
    {
        $user = new TestUser('test@example.com');
        $permissionCode = 'PERMISSION_USER_EDIT';

        // 测试方法调用不会抛出异常
        $this->expectNotToPerformAssertions();
        $this->auditLogger->logPermissionCheck($permissionCode, $user, true);
        $this->auditLogger->logPermissionCheck($permissionCode, $user, false);
    }

    public function testGenerateOperationIdReturnsUniqueId(): void
    {
        $id1 = $this->auditLogger->generateOperationId();
        $id2 = $this->auditLogger->generateOperationId();

        $this->assertNotEquals($id1, $id2);
        $this->assertMatchesRegularExpression('/^op_[a-f0-9]{13}$/', $id1);
        $this->assertMatchesRegularExpression('/^op_[a-f0-9]{13}$/', $id2);
    }
}
