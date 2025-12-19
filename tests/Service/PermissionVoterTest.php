<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Service\PermissionManager;
use Tourze\RoleBasedAccessControlBundle\Service\PermissionVoter;

/**
 * @internal
 */
#[CoversClass(PermissionVoter::class)]
#[RunTestsInSeparateProcesses]
class PermissionVoterTest extends AbstractIntegrationTestCase
{
    private PermissionVoter $voter;

    private UserInterface $user;

    private TokenInterface $token;

    protected function onSetUp(): void
    {
        // 从容器获取 PermissionVoter 服务
        $this->voter = self::getService(PermissionVoter::class);

        // 创建测试用户
        $this->user = new class('test-' . uniqid() . '@example.com') implements UserInterface {
            public function __construct(private string $email)
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->email;
            }

            /** @return array<string> */
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }
        };

        // 创建测试用的 Token
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
        // 用户默认没有权限，所以会返回ACCESS_DENIED而不是ACCESS_ABSTAIN
        $result = $this->voter->vote($this->token, null, ['PERMISSION_USER_EDIT']);
        // 如果supports返回true，vote会返回ACCESS_GRANTED或ACCESS_DENIED，而不是ACCESS_ABSTAIN
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
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
        $attribute = 'PERMISSION_USER_EDIT_' . uniqid();
        $roleCode = 'ROLE_TEST_USER_' . uniqid();

        // 创建角色
        $role = new Role();
        $role->setCode($roleCode);
        $role->setName('Test User Role');
        $this->persistAndFlush($role);

        // 创建权限
        $permission = new Permission();
        $permission->setCode($attribute);
        $permission->setName('User Edit Permission');
        $this->persistAndFlush($permission);

        // 为角色添加权限
        $permissionManager = self::getService(PermissionManager::class);
        $permissionManager->addPermissionToRole($roleCode, $attribute);

        // 为用户分配角色
        $permissionManager->assignRoleToUser($this->user, $roleCode);

        $result = $this->voter->vote($this->token, null, [$attribute]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteReturnAccessDeniedWhenUserDoesNotHavePermission(): void
    {
        // 测试：用户没有权限时返回ACCESS_DENIED
        $attribute = 'PERMISSION_USER_DELETE_' . uniqid();

        // 创建权限但不分配给用户
        $permission = new Permission();
        $permission->setCode($attribute);
        $permission->setName('User Delete Permission');
        $this->persistAndFlush($permission);

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
