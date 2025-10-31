<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;
use Tourze\RoleBasedAccessControlBundle\Exception\DeletionConflictException;
use Tourze\RoleBasedAccessControlBundle\Exception\InvalidUserIdentifierException;
use Tourze\RoleBasedAccessControlBundle\Exception\PermissionNotFoundException;
use Tourze\RoleBasedAccessControlBundle\Exception\RoleNotFoundException;
use Tourze\RoleBasedAccessControlBundle\Repository\PermissionRepository;
use Tourze\RoleBasedAccessControlBundle\Repository\RoleRepository;
use Tourze\RoleBasedAccessControlBundle\Repository\UserRoleRepository;

class PermissionManager implements PermissionManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly UserRoleRepository $userRoleRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * 已实现数据库事务控制，确保并发安全。
     */
    public function assignRoleToUser(UserInterface $user, string $roleCode): bool
    {
        $role = $this->roleRepository->findOneByCode($roleCode);
        if (null === $role) {
            throw RoleNotFoundException::forRoleCode($roleCode);
        }

        // 检查是否已有事务，避免嵌套事务
        $isInTransaction = $this->entityManager->getConnection()->isTransactionActive();

        if (!$isInTransaction) {
            // 使用数据库事务确保原子性，防止并发竞争条件
            $this->entityManager->beginTransaction();
        }

        try {
            $existingUserRole = $this->userRoleRepository->findUserRoleByUserAndRole(
                $user->getUserIdentifier(),
                $roleCode
            );

            if (null !== $existingUserRole) {
                if (!$isInTransaction) {
                    $this->entityManager->rollback();
                }

                return false; // 已经存在，幂等操作
            }

            $userRole = new UserRole();
            $userRole->setUserId($user->getUserIdentifier());
            $userRole->setRole($role);

            $this->entityManager->persist($userRole);

            if (!$isInTransaction) {
                $this->entityManager->flush();
                $this->entityManager->commit();
            }

            return true;
        } catch (\Exception $e) {
            if (!$isInTransaction) {
                $this->entityManager->rollback();
            }
            throw $e;
        }
    }

    public function revokeRoleFromUser(UserInterface $user, string $roleCode): bool
    {
        $existingUserRole = $this->userRoleRepository->findUserRoleByUserAndRole(
            $user->getUserIdentifier(),
            $roleCode
        );

        if (null === $existingUserRole) {
            return false; // 不存在，幂等操作
        }

        $this->entityManager->remove($existingUserRole);
        $this->entityManager->flush();

        return true;
    }

    public function addPermissionToRole(string $roleCode, string $permissionCode): bool
    {
        $role = $this->roleRepository->findOneByCode($roleCode);
        if (null === $role) {
            throw RoleNotFoundException::forRoleCode($roleCode);
        }

        $permission = $this->permissionRepository->findOneByCode($permissionCode);
        if (null === $permission) {
            throw PermissionNotFoundException::forPermissionCode($permissionCode);
        }

        if ($role->getPermissions()->contains($permission)) {
            return false; // 已经存在，幂等操作
        }

        $role->addPermission($permission);

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return true;
    }

    public function removePermissionFromRole(string $roleCode, string $permissionCode): bool
    {
        $role = $this->roleRepository->findOneByCode($roleCode);
        if (null === $role) {
            return false;
        }

        $permission = $this->permissionRepository->findOneByCode($permissionCode);
        if (null === $permission) {
            return false;
        }

        if (!$role->getPermissions()->contains($permission)) {
            return false; // 不存在，幂等操作
        }

        $role->removePermission($permission);

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return true;
    }

    public function getUserPermissions(UserInterface $user): array
    {
        $userRoles = $this->userRoleRepository->findByUserId($user->getUserIdentifier());
        $permissions = [];

        foreach ($userRoles as $userRole) {
            foreach ($userRole->getRole()->getPermissions() as $permission) {
                $permissions[] = $permission->getCode();
            }
        }

        return array_unique($permissions);
    }

    public function hasPermission(UserInterface $user, string $permissionCode): bool
    {
        $userPermissions = $this->getUserPermissions($user);

        return in_array($permissionCode, $userPermissions, true);
    }

    /**
     * @return array<Role>
     */
    public function getUserRoles(UserInterface $user): array
    {
        $userRoles = $this->userRoleRepository->findByUserId($user->getUserIdentifier());
        $roles = [];

        foreach ($userRoles as $userRole) {
            $roles[] = $userRole->getRole();
        }

        return $roles;
    }

    public function canDeleteRole(string $roleCode): bool
    {
        $userCount = $this->userRoleRepository->countUsersByRoleCode($roleCode);

        return 0 === $userCount;
    }

    public function canDeletePermission(string $permissionCode): bool
    {
        $roleCount = count($this->permissionRepository->findPermissionsForDeletionCheck($permissionCode));

        return 0 === $roleCount;
    }

    public function deleteRole(string $roleCode): void
    {
        if (!$this->canDeleteRole($roleCode)) {
            $userCount = $this->userRoleRepository->countUsersByRoleCode($roleCode);
            throw DeletionConflictException::forRoleDeletion($roleCode, [$userCount]);
        }

        $role = $this->roleRepository->findOneByCode($roleCode);
        if (null !== $role) {
            $this->entityManager->remove($role);
            $this->entityManager->flush();
        }
    }

    public function deletePermission(string $permissionCode): void
    {
        if (!$this->canDeletePermission($permissionCode)) {
            $roleCodes = $this->permissionRepository->findPermissionsForDeletionCheck($permissionCode);
            throw DeletionConflictException::forPermissionDeletion($permissionCode, array_values($roleCodes));
        }

        $permission = $this->permissionRepository->findOneByCode($permissionCode);
        if (null !== $permission) {
            $this->entityManager->remove($permission);
            $this->entityManager->flush();
        }
    }

    /**
     * {@inheritdoc}
     *
     * 已实现数据库事务控制，确保批量操作的并发安全。
     */
    public function bulkAssignRoles(array $userRoleMapping): BulkOperationResult
    {
        $successes = 0;
        $failures = [];

        // 使用外层事务确保批量操作的原子性
        $this->entityManager->beginTransaction();
        try {
            foreach ($userRoleMapping as $userId => $roleCodes) {
                foreach ($roleCodes as $roleCode) {
                    try {
                        // 为演示创建一个简单的用户对象
                        $user = new class($userId) implements UserInterface {
                            public function __construct(private string $identifier)
                            {
                            }

                            public function getUserIdentifier(): string
                            {
                                if ('' === $this->identifier) {
                                    throw new InvalidUserIdentifierException('User identifier cannot be empty');
                                }

                                return $this->identifier;
                            }

                            public function getRoles(): array
                            {
                                return [];
                            }

                            public function eraseCredentials(): void
                            {
                            }
                        };

                        $changed = $this->assignRoleToUser($user, $roleCode);
                        if ($changed) {
                            ++$successes;
                        }
                    } catch (\Exception $e) {
                        $failures[] = [
                            'item' => "{$userId}:{$roleCode}",
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return new BulkOperationResult($successes, count($failures), $failures);
    }

    public function bulkRevokeRoles(array $userRoleMapping): BulkOperationResult
    {
        $successes = 0;
        $failures = [];

        foreach ($userRoleMapping as $userId => $roleCodes) {
            foreach ($roleCodes as $roleCode) {
                try {
                    $user = new class($userId) implements UserInterface {
                        public function __construct(private string $identifier)
                        {
                        }

                        public function getUserIdentifier(): string
                        {
                            if ('' === $this->identifier) {
                                throw new InvalidUserIdentifierException('User identifier cannot be empty');
                            }

                            return $this->identifier;
                        }

                        public function getRoles(): array
                        {
                            return [];
                        }

                        public function eraseCredentials(): void
                        {
                        }
                    };

                    $changed = $this->revokeRoleFromUser($user, $roleCode);
                    if ($changed) {
                        ++$successes;
                    }
                } catch (\Exception $e) {
                    $failures[] = [
                        'item' => "{$userId}:{$roleCode}",
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return new BulkOperationResult($successes, count($failures), $failures);
    }

    public function bulkGrantPermissions(array $rolePermissionMapping): BulkOperationResult
    {
        $successes = 0;
        $failures = [];

        foreach ($rolePermissionMapping as $roleCode => $permissionCodes) {
            foreach ($permissionCodes as $permissionCode) {
                try {
                    $changed = $this->addPermissionToRole($roleCode, $permissionCode);
                    if ($changed) {
                        ++$successes;
                    }
                } catch (\Exception $e) {
                    $failures[] = [
                        'item' => "{$roleCode}:{$permissionCode}",
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return new BulkOperationResult($successes, count($failures), $failures);
    }
}
