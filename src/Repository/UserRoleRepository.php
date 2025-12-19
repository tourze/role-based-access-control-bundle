<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;

/**
 * @extends ServiceEntityRepository<UserRole>
 */
#[AsRepository(entityClass: UserRole::class)]
final class UserRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRole::class);
    }

    /**
     * 根据用户ID查找所有用户角色关联
     * @return UserRole[]
     */
    public function findByUserId(string $userId): array
    {
        $result = $this->createQueryBuilder('ur')
            ->innerJoin('ur.role', 'r')
            ->where('ur.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var UserRole[] $result */
        return $result;
    }

    /**
     * 根据角色代码查找所有用户角色关联
     * @return UserRole[]
     */
    public function findByRoleCode(string $roleCode): array
    {
        $result = $this->createQueryBuilder('ur')
            ->innerJoin('ur.role', 'r')
            ->where('r.code = :roleCode')
            ->setParameter('roleCode', $roleCode)
            ->orderBy('ur.assignTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var UserRole[] $result */
        return $result;
    }

    /**
     * 查找特定用户和角色的关联记录
     */
    public function findUserRoleByUserAndRole(string $userId, string $roleCode): ?UserRole
    {
        $result = $this->createQueryBuilder('ur')
            ->innerJoin('ur.role', 'r')
            ->where('ur.userId = :userId')
            ->andWhere('r.code = :roleCode')
            ->setParameter('userId', $userId)
            ->setParameter('roleCode', $roleCode)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof UserRole || null === $result);

        /** @var UserRole|null $result */
        return $result;
    }

    /**
     * 获取用户及其角色数量的统计信息
     * @return array<array{userId: string, roleCount: int}>
     */
    public function getUsersWithRoleCount(): array
    {
        $result = $this->createQueryBuilder('ur')
            ->select('ur.userId', 'COUNT(ur.id) as roleCount')
            ->groupBy('ur.userId')
            ->orderBy('roleCount', 'DESC')
            ->addOrderBy('ur.userId', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var array<array{userId: string, roleCount: int}> $result */
        return $result;
    }

    /**
     * 查找最近的角色分配记录
     * @return UserRole[]
     */
    public function findRecentAssignments(int $limit = 50): array
    {
        $result = $this->createQueryBuilder('ur')
            ->innerJoin('ur.role', 'r')
            ->orderBy('ur.assignTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var UserRole[] $result */
        return $result;
    }

    /**
     * 统计使用指定角色的用户数量
     */
    public function countUsersByRoleCode(string $roleCode): int
    {
        return (int) $this->createQueryBuilder('ur')
            ->select('COUNT(DISTINCT ur.userId)')
            ->innerJoin('ur.role', 'r')
            ->where('r.code = :roleCode')
            ->setParameter('roleCode', $roleCode)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 查找拥有多个角色的用户
     * @return array<array{userId: string, roleCount: int, roles: string[]}>
     */
    public function findUsersWithMultipleRoles(): array
    {
        $usersWithMultipleRoles = $this->createQueryBuilder('ur')
            ->select('ur.userId', 'COUNT(ur.id) as roleCount')
            ->groupBy('ur.userId')
            ->having('COUNT(ur.id) > 1')
            ->orderBy('roleCount', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($usersWithMultipleRoles));

        /** @var array<array{userId: string, roleCount: int}> $usersWithMultipleRoles */

        // 为每个用户获取角色列表
        $result = [];
        foreach ($usersWithMultipleRoles as $userInfo) {
            assert(is_array($userInfo) && isset($userInfo['userId']));

            $roles = $this->createQueryBuilder('ur')
                ->select('r.code')
                ->innerJoin('ur.role', 'r')
                ->where('ur.userId = :userId')
                ->setParameter('userId', $userInfo['userId'])
                ->getQuery()
                ->getSingleColumnResult()
            ;

            assert(is_numeric($userInfo['roleCount']));

            /** @var string[] $roles */
            $result[] = [
                'userId' => (string) $userInfo['userId'],
                'roleCount' => (int) $userInfo['roleCount'],
                'roles' => $roles,
            ];
        }

        return $result;
    }

    /**
     * 保存用户角色关联实体
     */
    public function save(UserRole $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除用户角色关联实体
     */
    public function remove(UserRole $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
