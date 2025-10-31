<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;

#[WithMonologChannel(channel: 'role_based_access_control')]
#[Autoconfigure(public: true)]
class AuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function logPermissionChange(string $action, array $data): void
    {
        $context = [
            'action' => $action,
            'data' => $data,
            'timestamp' => date('c'),
        ];

        $message = "Permission change: {$action}";

        // 根据操作类型选择日志级别
        if (str_contains($action, 'deleted') || str_contains($action, 'revoked')) {
            $this->logger->warning($message, $context);
        } else {
            $this->logger->info($message, $context);
        }
    }

    public function logPermissionCheck(string $permissionCode, UserInterface $user, bool $result): void
    {
        $context = [
            'permission_code' => $permissionCode,
            'user_id' => $user->getUserIdentifier(),
            'result' => $result,
            'timestamp' => date('c'),
        ];

        if ($result) {
            $this->logger->debug("Permission check: {$permissionCode}", $context);
        } else {
            $this->logger->warning("Permission denied: {$permissionCode}", $context);
        }
    }

    public function generateOperationId(): string
    {
        return 'op_' . uniqid();
    }
}
