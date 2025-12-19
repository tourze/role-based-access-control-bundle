<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;

/**
 * @extends ServiceEntityRepository<Role>
 */
#[AsRepository(entityClass: Role::class)]
final class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * 根据角色代码查找角色
     */
    public function findOneByCode(string $code): ?Role
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * 统计使用指定角色的用户数量
     */
    public function countUsersByRoleCode(string $roleCode): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(ur.id)')
            ->leftJoin('r.userRoles', 'ur')
            ->where('r.code = :roleCode')
            ->setParameter('roleCode', $roleCode)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 获取所有角色及其用户数量
     * @return array<array{role: Role, userCount: int}>
     */
    public function getRolesWithUserCount(): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r', 'COUNT(ur.id) as userCount')
            ->leftJoin('r.userRoles', 'ur')
            ->groupBy('r.id')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($results));

        // 确保每个结果都有正确的格式
        return array_map(static function (mixed $result): array {
            assert(is_array($result) && isset($result[0], $result['userCount']));
            assert($result[0] instanceof Role);
            assert(is_numeric($result['userCount']));

            return [
                'role' => $result[0],
                'userCount' => (int) $result['userCount'],
            ];
        }, $results);
    }

    /**
     * 查找角色以便进行删除检查
     * @return array<string> 返回关联的用户ID数组
     */
    public function findRolesForDeletionCheck(string $roleCode): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('ur.userId')
            ->leftJoin('r.userRoles', 'ur')
            ->where('r.code = :roleCode')
            ->setParameter('roleCode', $roleCode)
            ->getQuery()
            ->getArrayResult()
        ;

        // 提取用户ID，过滤掉null值
        $userIds = array_column($results, 'userId');

        /** @var array<string> $filteredUserIds */
        $filteredUserIds = array_filter($userIds, static fn ($userId) => null !== $userId);

        return array_values($filteredUserIds);
    }

    /**
     * 根据父角色ID查找子角色（为角色继承预留）
     * @return Role[]
     */
    public function findByParentRoleId(?int $parentRoleId): array
    {
        return $this->findBy(
            ['parentRoleId' => $parentRoleId],
            ['hierarchyLevel' => 'ASC']
        );
    }

    /**
     * 查找所有根角色（没有父角色的角色）
     * @return Role[]
     */
    public function findRootRoles(): array
    {
        return $this->findBy(
            ['parentRoleId' => null],
            ['name' => 'ASC']
        );
    }

    /**
     * 获取角色层次结构（为将来的角色继承功能预留）
     * @return Role[]
     */
    public function findRoleHierarchy(?int $maxLevel = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.hierarchyLevel', 'ASC')
            ->addOrderBy('r.name', 'ASC')
        ;

        if (null !== $maxLevel) {
            $qb->where('r.hierarchyLevel <= :maxLevel')
                ->setParameter('maxLevel', $maxLevel)
            ;
        }

        $result = $qb->getQuery()->getResult();
        assert(is_array($result));

        /** @var Role[] $result */
        return $result;
    }

    /**
     * 搜索角色（支持名称和代码模糊匹配）
     * @return Role[]
     */
    public function searchRoles(string $query, int $limit = 10): array
    {
        $result = $this->createQueryBuilder('r')
            ->where('r.name LIKE :query OR r.code LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Role[] $result */
        return $result;
    }

    /**
     * 保存角色实体
     */
    public function save(Role $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除角色实体
     */
    public function remove(Role $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
