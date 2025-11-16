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
use Tourze\RoleBasedAccessControlBundle\Repository\PermissionRepository;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
#[ORM\Table(name: 'rbac_permission', options: ['comment' => 'RBAC权限表'])]
#[ORM\UniqueConstraint(name: 'permission_code_unique', columns: ['code'])]
class Permission implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '权限ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => '权限代码'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^PERMISSION_[A-Z_]+$/', message: '权限代码必须以PERMISSION_开头且仅包含大写字母和下划线')]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '权限名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '权限描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, mappedBy: 'permissions')]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
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

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
