<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\RoleBasedAccessControlBundle\Repository\RoleRepository;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'rbac_role', options: ['comment' => '角色表'])]
#[ORM\UniqueConstraint(name: 'role_code_unique', columns: ['code'])]
class Role implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => '角色编码'])]
    #[Assert\NotBlank(message: '角色编码不能为空')]
    #[Assert\Length(min: 1, max: 255, maxMessage: '角色编码长度不能超过255个字符')]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '角色名称'])]
    #[Assert\NotBlank(message: '角色名称不能为空')]
    #[Assert\Length(min: 1, max: 255, maxMessage: '角色名称长度不能超过255个字符')]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '角色描述'])]
    #[Assert\Length(max: 1000, maxMessage: '角色描述长度不能超过1000个字符')]
    private ?string $description = null;

    #[ORM\Column(name: 'parent_role_id', type: Types::INTEGER, nullable: true, options: ['comment' => '父角色ID'])]
    #[Assert\Type(type: 'integer', message: '父角色ID必须是整数')]
    #[Assert\PositiveOrZero(message: '父角色ID必须是正整数或零')]
    #[IndexColumn]
    private ?int $parentRoleId = null;

    #[ORM\Column(name: 'hierarchy_level', type: Types::INTEGER, nullable: true, options: ['comment' => '层级深度'])]
    #[Assert\Type(type: 'integer', message: '层级深度必须是整数')]
    #[Assert\PositiveOrZero(message: '层级深度必须是正整数或零')]
    #[Assert\LessThanOrEqual(value: 10, message: '层级深度不能超过10级')]
    private ?int $hierarchyLevel = null;

    /**
     * @var Collection<int, Permission>
     */
    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'roles')]
    #[ORM\JoinTable(
        name: 'rbac_role_permission',
        joinColumns: [new ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'permission_id', referencedColumnName: 'id')]
    )]
    private Collection $permissions;

    /**
     * @var Collection<int, UserRole>
     */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: UserRole::class)]
    private Collection $userRoles;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->userRoles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getParentRoleId(): ?int
    {
        return $this->parentRoleId;
    }

    public function setParentRoleId(?int $parentRoleId): void
    {
        $this->parentRoleId = $parentRoleId;
    }

    public function getHierarchyLevel(): ?int
    {
        return $this->hierarchyLevel;
    }

    public function setHierarchyLevel(?int $hierarchyLevel): void
    {
        $this->hierarchyLevel = $hierarchyLevel;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        $this->permissions->removeElement($permission);

        return $this;
    }

    /**
     * @return Collection<int, UserRole>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function __toString(): string
    {
        return $this->name ?? $this->code ?? '';
    }
}
