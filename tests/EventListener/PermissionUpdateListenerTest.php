<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;
use Tourze\RoleBasedAccessControlBundle\EventListener\PermissionUpdateListener;

/**
 * @internal
 */
#[CoversClass(PermissionUpdateListener::class)]
#[RunTestsInSeparateProcesses]
class PermissionUpdateListenerTest extends AbstractIntegrationTestCase
{
    private PermissionUpdateListener $listener;

    public function testListenerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(PermissionUpdateListener::class, $this->listener);
    }

    public function testPreUpdateSetsUpdatedAtToCurrentTime(): void
    {
        $permission = new Permission();
        $originalUpdateTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $permission->setUpdateTime($originalUpdateTime);

        $changeSet = [];
        $em = self::getEntityManager();
        $entity = new \stdClass();
        $args = new PreUpdateEventArgs($entity, $em, $changeSet);

        $this->listener->preUpdate($permission, $args);

        $updateTime = $permission->getUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);
        $this->assertNotEquals($originalUpdateTime, $updateTime);

        $now = new \DateTimeImmutable();
        $timeDiff = abs($now->getTimestamp() - $updateTime->getTimestamp());
        $this->assertLessThanOrEqual(2, $timeDiff, 'Updated timestamp should be close to current time');
    }

    public function testPreUpdateDoesNotAffectOtherProperties(): void
    {
        $permission = new Permission();
        $permission->setCode('PERMISSION_TEST');
        $permission->setName('Test Permission');
        $permission->setDescription('Test description');
        $originalCreateTime = new \DateTimeImmutable('2024-01-01 09:00:00');
        $permission->setCreateTime($originalCreateTime);

        $changeSet = [];
        $em = self::getEntityManager();
        $entity = new \stdClass();
        $args = new PreUpdateEventArgs($entity, $em, $changeSet);

        $this->listener->preUpdate($permission, $args);

        $this->assertEquals('PERMISSION_TEST', $permission->getCode());
        $this->assertEquals('Test Permission', $permission->getName());
        $this->assertEquals('Test description', $permission->getDescription());
        $this->assertEquals($originalCreateTime, $permission->getCreateTime());
    }

    public function testPreUpdateWorksWithMinimalPermission(): void
    {
        $permission = new Permission();
        $changeSet = [];
        $em = self::getEntityManager();
        $entity = new \stdClass();
        $args = new PreUpdateEventArgs($entity, $em, $changeSet);

        $this->listener->preUpdate($permission, $args);

        $updateTime = $permission->getUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);
    }

    protected function onSetUp(): void
    {
        $this->listener = self::getService(PermissionUpdateListener::class);
    }
}
