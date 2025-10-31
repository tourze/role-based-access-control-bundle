<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    private string $email;

    /**
     * @var array<string>
     */
    private array $roles = [];

    /**
     * @var array<string>
     */
    private array $permissions = [];

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getUserIdentifier(): string
    {
        return '' !== $this->email ? $this->email : 'anonymous';
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return array_unique(array_merge($this->roles, ['ROLE_USER']));
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getId(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase in test user
    }

    /**
     * @param array<string> $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    /**
     * @return array<string>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
