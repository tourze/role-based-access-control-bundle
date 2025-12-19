<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Service;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, mixed>
 */
final class PermissionVoter extends Voter
{
    public function __construct(
        private readonly PermissionManagerInterface $permissionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // 只处理以PERMISSION_开头的属性
        return str_starts_with($attribute, 'PERMISSION_');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // 用户必须是UserInterface的实例
        if (!$user instanceof UserInterface) {
            return false;
        }

        // 检查用户是否具有指定权限
        return $this->permissionManager->hasPermission($user, $attribute);
    }
}
