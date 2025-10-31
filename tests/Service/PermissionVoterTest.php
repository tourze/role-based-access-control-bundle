<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Service\BulkOperationResult;
use Tourze\RoleBasedAccessControlBundle\Service\PermissionManagerInterface;
use Tourze\RoleBasedAccessControlBundle\Service\PermissionVoter;
use Tourze\RoleBasedAccessControlBundle\Tests\TestUser;

/**
 * @internal
 */
#[CoversClass(PermissionVoter::class)]
class PermissionVoterTest extends TestCase
{
    private PermissionVoter $voter;

    /**
     * @var PermissionManagerInterface&object{setHasPermissionResult: callable(UserInterface, string, bool): void}
     */
    private PermissionManagerInterface $permissionManager;

    private TokenInterface $token;

    private UserInterface $user;

    protected function setUp(): void
    {
        parent::setUp();

        // @phpstan-ignore assign.propertyType
        $this->permissionManager = new class implements PermissionManagerInterface {
            /** @var array<string, bool> */
            private array $hasPermissionResults = [];

            public function setHasPermissionResult(UserInterface $user, string $permission, bool $result): void
            {
                $this->hasPermissionResults[$user->getUserIdentifier() . ':' . $permission] = $result;
            }

            public function hasPermission(UserInterface $user, string $permission): bool
            {
                return $this->hasPermissionResults[$user->getUserIdentifier() . ':' . $permission] ?? false;
            }

            // 其他必需方法的占位实现
            public function assignRoleToUser(UserInterface $user, string $roleCode): bool
            {
                return false;
            }

            public function revokeRoleFromUser(UserInterface $user, string $roleCode): bool
            {
                return false;
            }

            public function addPermissionToRole(string $roleCode, string $permissionCode): bool
            {
                return false;
            }

            public function removePermissionFromRole(string $roleCode, string $permissionCode): bool
            {
                return false;
            }

            /** @return array<string> */
            public function getUserPermissions(UserInterface $user): array
            {
                return [];
            }

            public function canDeleteRole(string $roleCode): bool
            {
                return false;
            }

            public function deleteRole(string $roleCode): void
            {
            }

            public function canDeletePermission(string $permissionCode): bool
            {
                return false;
            }

            public function deletePermission(string $permissionCode): void
            {
            }

            /** @param array<mixed> $userRoleMapping */
            public function bulkAssignRoles(array $userRoleMapping): BulkOperationResult
            {
                throw new \Exception('Not implemented');
            }

            /** @param array<mixed> $userRoleMapping */
            public function bulkRevokeRoles(array $userRoleMapping): BulkOperationResult
            {
                throw new \Exception('Not implemented');
            }

            /** @param array<mixed> $rolePermissionMapping */
            public function bulkGrantPermissions(array $rolePermissionMapping): BulkOperationResult
            {
                throw new \Exception('Not implemented');
            }

            /** @return array<Role> */
            public function getUserRoles(UserInterface $user): array
            {
                return [];
            }
        };

        $this->voter = new PermissionVoter($this->permissionManager);

        $this->user = new TestUser('test@example.com');
        $this->token = new class($this->user) implements TokenInterface {
            private UserInterface $user;

            public function __construct(UserInterface $user)
            {
                $this->user = $user;
            }

            public function getUser(): UserInterface
            {
                return $this->user;
            }

            public function __toString(): string
            {
                return 'token';
            }

            /** @return array<string> */
            public function getRoleNames(): array
            {
                return [];
            }

            public function getCredentials(): mixed
            {
                return null;
            }

            public function eraseCredentials(): void
            {
            }

            /** @return array<mixed> */
            public function getAttributes(): array
            {
                return [];
            }

            /** @param array<mixed> $attributes */
            public function setAttributes(array $attributes): void
            {
            }

            public function hasAttribute(string $name): bool
            {
                return false;
            }

            public function getAttribute(string $name): mixed
            {
                return null;
            }

            public function setAttribute(string $name, mixed $value): void
            {
            }

            public function getUsername(): string
            {
                return $this->user->getUserIdentifier();
            }

            public function getUserIdentifier(): string
            {
                return $this->user->getUserIdentifier();
            }

            public function setUser(UserInterface $user): void
            {
                $this->user = $user;
            }

            /** @return array<mixed> */
            public function __serialize(): array
            {
                return [];
            }

            /** @param array<mixed> $data */
            public function __unserialize(array $data): void
            {
            }
        };
    }

    public function testSupportsReturnsTrueForPermissionAttributes(): void
    {
        // 测试：PERMISSION_*格式的属性应该被支持 - 通过vote方法间接测试
        // @phpstan-ignore method.notFound
        $this->permissionManager->setHasPermissionResult($this->user, 'PERMISSION_USER_EDIT', true);

        $result = $this->voter->vote($this->token, null, ['PERMISSION_USER_EDIT']);
        // 如果supports返回true，vote会返回ACCESS_GRANTED或ACCESS_DENIED，而不是ACCESS_ABSTAIN
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonPermissionAttributes(): void
    {
        // 测试：非PERMISSION_*格式的属性应该不被支持 - 通过vote方法间接测试
        $result1 = $this->voter->vote($this->token, null, ['ROLE_ADMIN']);
        $result2 = $this->voter->vote($this->token, null, ['USER_EDIT']);
        $result3 = $this->voter->vote($this->token, null, ['IS_AUTHENTICATED_FULLY']);

        // 不支持的属性应该返回ACCESS_ABSTAIN
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result1);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result2);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result3);
    }

    public function testVoteReturnAccessGrantedWhenUserHasPermission(): void
    {
        // 测试：用户有权限时返回ACCESS_GRANTED
        $attribute = 'PERMISSION_USER_EDIT';

        // @phpstan-ignore method.notFound
        $this->permissionManager->setHasPermissionResult($this->user, $attribute, true);

        $result = $this->voter->vote($this->token, null, [$attribute]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteReturnAccessDeniedWhenUserDoesNotHavePermission(): void
    {
        // 测试：用户没有权限时返回ACCESS_DENIED
        $attribute = 'PERMISSION_USER_DELETE';

        // @phpstan-ignore method.notFound
        $this->permissionManager->setHasPermissionResult($this->user, $attribute, false);

        $result = $this->voter->vote($this->token, null, [$attribute]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteReturnAccessAbstainForUnsupportedAttributes(): void
    {
        // 测试：不支持的属性返回ACCESS_ABSTAIN
        $result = $this->voter->vote($this->token, null, ['ROLE_ADMIN']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteReturnAccessDeniedWhenUserNotAuthenticated(): void
    {
        // 测试：用户未认证时返回ACCESS_DENIED
        $unauthenticatedToken = new class implements TokenInterface {
            public function getUser(): ?UserInterface
            {
                return null;
            }

            public function __toString(): string
            {
                return 'token';
            }

            /** @return array<string> */
            public function getRoleNames(): array
            {
                return [];
            }

            public function getCredentials(): mixed
            {
                return null;
            }

            public function eraseCredentials(): void
            {
            }

            /** @return array<mixed> */
            public function getAttributes(): array
            {
                return [];
            }

            /** @param array<mixed> $attributes */
            public function setAttributes(array $attributes): void
            {
            }

            public function hasAttribute(string $name): bool
            {
                return false;
            }

            public function getAttribute(string $name): mixed
            {
                return null;
            }

            public function setAttribute(string $name, mixed $value): void
            {
            }

            public function getUsername(): string
            {
                return '';
            }

            public function getUserIdentifier(): string
            {
                return '';
            }

            public function setUser(UserInterface $user): void
            {
            }

            /** @return array<mixed> */
            public function __serialize(): array
            {
                return [];
            }

            /** @param array<mixed> $data */
            public function __unserialize(array $data): void
            {
            }
        };

        $result = $this->voter->vote($unauthenticatedToken, null, ['PERMISSION_USER_EDIT']);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
