<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Service;

use Symfony\Component\Security\Core\User\UserInterface;

interface AuditLoggerInterface
{
    /**
     * 记录权限变更操作
     * @param string $action 操作类型
     * @param array<string, mixed> $data 操作数据
     */
    public function logPermissionChange(string $action, array $data): void;

    /**
     * 记录权限检查操作
     * @param string $permissionCode 权限代码
     * @param UserInterface $user 用户
     * @param bool $result 检查结果
     */
    public function logPermissionCheck(string $permissionCode, UserInterface $user, bool $result): void;

    /**
     * 生成唯一操作ID
     * @return string
     */
    public function generateOperationId(): string;
}
