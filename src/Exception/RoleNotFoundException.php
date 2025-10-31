<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Exception;

class RoleNotFoundException extends \InvalidArgumentException
{
    private string $roleCode;

    public function __construct(string $message, string $roleCode = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->roleCode = $roleCode;
    }

    public static function forRoleCode(string $roleCode): self
    {
        return new self(
            sprintf('Role with code "%s" not found', $roleCode),
            $roleCode
        );
    }

    public function getRoleCode(): string
    {
        return $this->roleCode;
    }
}
