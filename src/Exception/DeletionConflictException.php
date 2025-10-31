<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Exception;

class DeletionConflictException extends \RuntimeException
{
    private string $entityIdentifier;

    /** @var array<int, mixed> */
    private array $affectedEntities;

    /**
     * @param array<int, mixed> $affectedEntities
     */
    public function __construct(string $message, string $entityIdentifier = '', array $affectedEntities = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->entityIdentifier = $entityIdentifier;
        $this->affectedEntities = $affectedEntities;
    }

    /**
     * @param array<int, mixed> $affectedEntities
     */
    public static function forRoleDeletion(string $roleCode, array $affectedEntities): self
    {
        $message = sprintf(
            'Cannot delete role "%s": %d users are assigned to this role',
            $roleCode,
            count($affectedEntities)
        );

        return new self($message, $roleCode, $affectedEntities);
    }

    /**
     * @param array<int, mixed> $affectedEntities
     */
    public static function forPermissionDeletion(string $permissionCode, array $affectedEntities): self
    {
        $message = sprintf(
            'Cannot delete permission "%s": %d roles have this permission',
            $permissionCode,
            count($affectedEntities)
        );

        return new self($message, $permissionCode, $affectedEntities);
    }

    public function getEntityIdentifier(): string
    {
        return $this->entityIdentifier;
    }

    /**
     * @return array<int, mixed>
     */
    public function getAffectedEntities(): array
    {
        return $this->affectedEntities;
    }
}
