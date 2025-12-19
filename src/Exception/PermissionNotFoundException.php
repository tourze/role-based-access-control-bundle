<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Exception;

final class PermissionNotFoundException extends \InvalidArgumentException
{
    private string $permissionCode;

    public function __construct(string $message, string $permissionCode = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->permissionCode = $permissionCode;
    }

    public static function forPermissionCode(string $permissionCode): self
    {
        return new self(
            sprintf('Permission with code "%s" not found', $permissionCode),
            $permissionCode
        );
    }

    public function getPermissionCode(): string
    {
        return $this->permissionCode;
    }
}
