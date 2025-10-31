<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;

/**
 * @extends ServiceEntityRepository<Permission>
 */
#[AsRepository(entityClass: Permission::class)]
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * 根据权限代码查找权限
     */
    public function findOneByCode(string $code): ?Permission
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * 查找指定角色的所有权限
     * @return Permission[]
     */
    public function findPermissionsForRole(string $roleCode): array
    {
        $result = $this->createQueryBuilder('p')
            ->innerJoin('p.roles', 'r')
            ->where('r.code = :roleCode')
            ->setParameter('roleCode', $roleCode)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Permission[] $result */
        return $result;
    }

    /**
     * 查找未分配给任何角色的权限
     * @return Permission[]
     */
    public function findUnassignedPermissions(): array
    {
        $result = $this->createQueryBuilder('p')
            ->leftJoin('p.roles', 'r')
            ->where('r.id IS NULL')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Permission[] $result */
        return $result;
    }

    /**
     * 获取所有权限及其关联的角色数量
     * @return array<array{permission: Permission, roleCount: int}>
     */
    public function getPermissionsWithRoleCount(): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('p', 'COUNT(r.id) as roleCount')
            ->leftJoin('p.roles', 'r')
            ->groupBy('p.id')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($results));

        // 确保每个结果都有正确的格式
        return array_map(static function (mixed $result): array {
            assert(is_array($result) && isset($result[0], $result['roleCount']));
            assert($result[0] instanceof Permission);
            assert(is_numeric($result['roleCount']));

            return [
                'permission' => $result[0],
                'roleCount' => (int) $result['roleCount'],
            ];
        }, $results);
    }

    /**
     * 查找权限以便进行删除检查
     * @return array<string> 返回关联的角色代码数组
     */
    public function findPermissionsForDeletionCheck(string $permissionCode): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('r.code')
            ->innerJoin('p.roles', 'r')
            ->where('p.code = :permissionCode')
            ->setParameter('permissionCode', $permissionCode)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        /** @var string[] $result */
        return $result;
    }

    /**
     * 搜索权限（支持名称和代码模糊匹配）
     * @return Permission[]
     */
    public function searchPermissions(string $query, int $limit = 10): array
    {
        $result = $this->createQueryBuilder('p')
            ->where('p.name LIKE :query OR p.code LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Permission[] $result */
        return $result;
    }

    /**
     * 根据权限代码模式查找权限
     * @return Permission[]
     */
    public function findByCodePattern(string $pattern): array
    {
        $result = $this->createQueryBuilder('p')
            ->where('p.code LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->orderBy('p.code', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Permission[] $result */
        return $result;
    }

    /**
     * 保存权限实体
     */
    public function save(Permission $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除权限实体
     */
    public function remove(Permission $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
