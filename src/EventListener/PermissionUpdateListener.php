<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Permission::class)]
class PermissionUpdateListener
{
    public function preUpdate(Permission $permission, PreUpdateEventArgs $args): void
    {
        $permission->setUpdateTime(new \DateTimeImmutable());
    }
}
