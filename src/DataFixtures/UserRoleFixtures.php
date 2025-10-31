<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;

class UserRoleFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_ROLE_ADMIN_REFERENCE = 'user-role-admin';
    public const USER_ROLE_USER_REFERENCE = 'user-role-user';
    public const USER_ROLE_MANAGER_REFERENCE = 'user-role-manager';

    public function load(ObjectManager $manager): void
    {
        /** @var Role $adminRole */
        $adminRole = $this->getReference(RoleFixtures::ROLE_ADMIN_REFERENCE, Role::class);
        /** @var Role $userRole */
        $userRole = $this->getReference(RoleFixtures::ROLE_USER_REFERENCE, Role::class);
        /** @var Role $managerRole */
        $managerRole = $this->getReference(RoleFixtures::ROLE_MANAGER_REFERENCE, Role::class);

        $userRoleAdmin = new UserRole();
        $userRoleAdmin->setRole($adminRole);
        $userRoleAdmin->setUserId('admin_user_1');
        $userRoleAdmin->setAssignTime(new \DateTimeImmutable());
        $manager->persist($userRoleAdmin);
        $this->addReference(self::USER_ROLE_ADMIN_REFERENCE, $userRoleAdmin);

        $userRoleUser = new UserRole();
        $userRoleUser->setRole($userRole);
        $userRoleUser->setUserId('normal_user_1');
        $userRoleUser->setAssignTime(new \DateTimeImmutable());
        $manager->persist($userRoleUser);
        $this->addReference(self::USER_ROLE_USER_REFERENCE, $userRoleUser);

        $userRoleManager = new UserRole();
        $userRoleManager->setRole($managerRole);
        $userRoleManager->setUserId('manager_user_1');
        $userRoleManager->setAssignTime(new \DateTimeImmutable());
        $manager->persist($userRoleManager);
        $this->addReference(self::USER_ROLE_MANAGER_REFERENCE, $userRoleManager);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }
}
