<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Service;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RoleBasedAccessControlBundle\DTO\BulkOperationResult;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;

/**
 * 权限管理服务接口
 *
 * 注意：涉及用户角色分配的操作具有并发敏感性，在实现时需要考虑适当的并发控制机制。
 */
interface PermissionManagerInterface
{
    /**
     * 为用户分配角色（幂等操作）
     *
     * 注意：此操作涉及并发敏感的用户角色分配，实现类已使用数据库事务确保并发安全。
     * ignore concurrency - 接口方法，实现类已处理并发控制
     *
     * @param UserInterface $user 目标用户
     * @param string $roleCode 角色代码
     * @return bool 是否发生了变更
     * @throws \InvalidArgumentException 角色不存在时
     */
    public function assignRoleToUser(UserInterface $user, string $roleCode): bool;

    /**
     * 从用户撤销角色（幂等操作）
     * @param UserInterface $user 目标用户
     * @param string $roleCode 角色代码
     * @return bool 是否发生了变更
     */
    public function revokeRoleFromUser(UserInterface $user, string $roleCode): bool;

    /**
     * 为角色添加权限（幂等操作）
     * @param string $roleCode 角色代码
     * @param string $permissionCode 权限代码
     * @return bool 是否发生了变更
     * @throws \InvalidArgumentException 角色或权限不存在时
     */
    public function addPermissionToRole(string $roleCode, string $permissionCode): bool;

    /**
     * 从角色移除权限（幂等操作）
     * @param string $roleCode 角色代码
     * @param string $permissionCode 权限代码
     * @return bool 是否发生了变更
     */
    public function removePermissionFromRole(string $roleCode, string $permissionCode): bool;

    /**
     * 获取用户的所有权限（优化的单查询）
     * @param UserInterface $user 目标用户
     * @return array<string> 权限代码数组
     */
    public function getUserPermissions(UserInterface $user): array;

    /**
     * 检查用户是否具有指定权限
     * @param UserInterface $user 目标用户
     * @param string $permissionCode 权限代码
     * @return bool
     */
    public function hasPermission(UserInterface $user, string $permissionCode): bool;

    /**
     * 获取用户的所有角色
     * @param UserInterface $user 目标用户
     * @return array<Role> 角色实体数组
     */
    public function getUserRoles(UserInterface $user): array;

    /**
     * 检查角色是否可以删除
     * @param string $roleCode 角色代码
     * @return bool
     */
    public function canDeleteRole(string $roleCode): bool;

    /**
     * 检查权限是否可以删除
     * @param string $permissionCode 权限代码
     * @return bool
     */
    public function canDeletePermission(string $permissionCode): bool;

    /**
     * 删除角色（硬删除，带前置检查）
     * @param string $roleCode 角色代码
     * @throws \RuntimeException 存在关联时
     */
    public function deleteRole(string $roleCode): void;

    /**
     * 删除权限（硬删除，带前置检查）
     * @param string $permissionCode 权限代码
     * @throws \RuntimeException 存在关联时
     */
    public function deletePermission(string $permissionCode): void;

    /**
     * 批量分配角色
     *
     * 注意：此操作涉及大量并发敏感的用户角色分配，实现类已使用数据库事务确保并发安全。
     * 考虑到性能影响，建议在处理大量数据时采用分批处理策略。
     * ignore concurrency - 接口方法，实现类已处理并发控制
     *
     * @param array<string, array<string>> $userRoleMapping 用户ID => 角色代码数组
     * @return BulkOperationResult
     */
    public function bulkAssignRoles(array $userRoleMapping): BulkOperationResult;

    /**
     * 批量撤销角色
     * @param array<string, array<string>> $userRoleMapping 用户ID => 角色代码数组
     * @return BulkOperationResult
     */
    public function bulkRevokeRoles(array $userRoleMapping): BulkOperationResult;

    /**
     * 批量授予权限
     * @param array<string, array<string>> $rolePermissionMapping 角色代码 => 权限代码数组
     * @return BulkOperationResult
     */
    public function bulkGrantPermissions(array $rolePermissionMapping): BulkOperationResult;
}
