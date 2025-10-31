<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\EventListener\RoleUpdateListener;

/**
 * @internal
 */
#[CoversClass(RoleUpdateListener::class)]
#[RunTestsInSeparateProcesses]
class RoleUpdateListenerTest extends AbstractIntegrationTestCase
{
    private RoleUpdateListener $listener;

    public function testListenerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(RoleUpdateListener::class, $this->listener);
    }

    public function testPreUpdateSetsUpdatedAtToCurrentTime(): void
    {
        $role = new Role();
        $originalUpdateTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $role->setUpdateTime($originalUpdateTime);

        $changeSet = [];
        $mockEm = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $args = new PreUpdateEventArgs($entity, $mockEm, $changeSet);

        $this->listener->preUpdate($role, $args);

        $updateTime = $role->getUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);
        $this->assertNotEquals($originalUpdateTime, $updateTime);

        $now = new \DateTimeImmutable();
        $timeDiff = abs($now->getTimestamp() - $updateTime->getTimestamp());
        $this->assertLessThanOrEqual(2, $timeDiff, 'Updated timestamp should be close to current time');
    }

    public function testPreUpdateDoesNotAffectOtherProperties(): void
    {
        $role = new Role();
        $role->setCode('ROLE_TEST');
        $role->setName('Test Role');
        $role->setDescription('Test description');
        $role->setParentRoleId(1);
        $role->setHierarchyLevel(2);
        $originalCreateTime = new \DateTimeImmutable('2024-01-01 09:00:00');
        $role->setCreateTime($originalCreateTime);

        $changeSet = [];
        $mockEm = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $args = new PreUpdateEventArgs($entity, $mockEm, $changeSet);

        $this->listener->preUpdate($role, $args);

        $this->assertEquals('ROLE_TEST', $role->getCode());
        $this->assertEquals('Test Role', $role->getName());
        $this->assertEquals('Test description', $role->getDescription());
        $this->assertEquals(1, $role->getParentRoleId());
        $this->assertEquals(2, $role->getHierarchyLevel());
        $this->assertEquals($originalCreateTime, $role->getCreateTime());
    }

    public function testPreUpdateWorksWithMinimalRole(): void
    {
        $role = new Role();
        $changeSet = [];
        $mockEm = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $args = new PreUpdateEventArgs($entity, $mockEm, $changeSet);

        $this->listener->preUpdate($role, $args);

        $updateTime = $role->getUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);
    }

    public function testPreUpdateUsesDateTimeNotDateTimeImmutable(): void
    {
        $role = new Role();
        $changeSet = [];
        $mockEm = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();
        $args = new PreUpdateEventArgs($entity, $mockEm, $changeSet);

        $this->listener->preUpdate($role, $args);

        $updateTime = $role->getUpdateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updateTime);

        $now = new \DateTimeImmutable();
        $timeDiff = abs($now->getTimestamp() - $updateTime->getTimestamp());
        $this->assertLessThanOrEqual(2, $timeDiff);
    }

    protected function onSetUp(): void
    {
        $this->listener = self::getService(RoleUpdateListener::class);
    }
}
