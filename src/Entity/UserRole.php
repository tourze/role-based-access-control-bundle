<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\RoleBasedAccessControlBundle\Repository\UserRoleRepository;

#[ORM\Entity(repositoryClass: UserRoleRepository::class)]
#[ORM\Table(name: 'rbac_user_role', options: ['comment' => '用户角色关联表'])]
#[ORM\UniqueConstraint(name: 'user_role_unique', columns: ['user_id', 'role_id'])]
class UserRole implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: Types::STRING, length: 255, options: ['comment' => '用户ID'])]
    #[Assert\NotBlank(message: '用户ID不能为空')]
    #[Assert\Length(min: 1, max: 255, maxMessage: '用户ID长度不能超过255个字符')]
    #[IndexColumn]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'userRoles')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false)]
    private Role $role;

    #[ORM\Column(name: 'assign_time', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '分配时间'])]
    #[Assert\NotNull(message: '分配时间不能为空')]
    #[IndexColumn]
    private \DateTimeImmutable $assignTime;

    // 不持久化的用户对象，仅用于内存中操作
    #[Assert\Valid]
    private ?UserInterface $user = null;

    public function __construct()
    {
        $this->assignTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
        $this->userId = $user->getUserIdentifier();
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }

    public function getAssignTime(): \DateTimeImmutable
    {
        return $this->assignTime;
    }

    public function setAssignTime(\DateTimeImmutable $assignTime): void
    {
        $this->assignTime = $assignTime;
    }

    public function __toString(): string
    {
        return sprintf(
            'User %s has role %s',
            $this->userId,
            $this->role->getCode()
        );
    }
}
