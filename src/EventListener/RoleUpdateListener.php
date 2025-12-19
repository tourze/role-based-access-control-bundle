<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Role::class)]
final class RoleUpdateListener
{
    public function preUpdate(Role $role, PreUpdateEventArgs $args): void
    {
        $role->setUpdateTime(new \DateTimeImmutable());
    }
}
