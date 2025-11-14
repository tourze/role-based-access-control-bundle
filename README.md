# Role-Based Access Control Bundle

基于角色的访问控制(RBAC)Bundle，为 PHP Monorepo 提供完整的权限管理系统

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.3-black.svg)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[English](README.md) | [中文](README.zh-CN.md)

## 功能特性

- **完整 RBAC 系统**：提供角色、权限、用户角色的完整管理
- **无缝集成**：与 Symfony Security 系统深度集成，支持 `#[IsGranted]` 注解
- **灵活权限控制**：基于自定义 Voter 实现细粒度权限管理
- **EasyAdmin 集成**：提供开箱即用的管理界面
- **命令行工具**：丰富的 CLI 命令简化权限管理
- **事件驱动**：完整的事件系统支持权限审计
- **性能优化**：高效的权限查询和缓存机制

## 安装指南

### 安装依赖

```bash
composer require tourze/role-based-access-control-bundle
```

### 注册 Bundle

在 `config/bundles.php` 中添加：

```php
return [
    // ...
    Tourze\RoleBasedAccessControlBundle\RoleBasedAccessControlBundle::class => ['all' => true],
];
```

### 数据库架构

创建必要的数据库表：

```sql
-- 角色表
CREATE TABLE `rbac_role` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `parent_role_id` INT NULL,
    `hierarchy_level` INT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `role_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 权限表
CREATE TABLE `rbac_permission` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `permission_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 用户角色关联表
CREATE TABLE `user_role` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    UNIQUE KEY `user_role_unique` (`user_id`, `role_id`),
    KEY `idx_user_role_user` (`user_id`),
    KEY `idx_user_role_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 角色权限关联表
CREATE TABLE `rbac_role_permission` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_role_permission_role` (`role_id`),
    KEY `idx_role_permission_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 使用方法

### 在 Controller 中使用权限

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[IsGranted('PERMISSION_USER_EDIT')]
    public function editUser(): Response
    {
        // 只有拥有 PERMISSION_USER_EDIT 权限的用户才能访问
    }

    #[IsGranted('PERMISSION_ORDER_VIEW')]
    public function viewOrder(): Response
    {
        // 只有拥有 PERMISSION_ORDER_VIEW 权限的用户才能访问
    }
}
```

### 在 Service 中使用权限

```php
use Symfony\Component\Security\Core\Security;

class OrderService
{
    public function __construct(private Security $security)
    {
    }

    public function deleteOrder(int $orderId): void
    {
        if ($this->security->isGranted('PERMISSION_ORDER_DELETE')) {
            // 执行删除操作
        } else {
            throw new AccessDeniedException('删除订单权限不足');
        }
    }
}
```

### 在 Twig 模板中使用权限

```twig
{% if is_granted('PERMISSION_USER_CREATE') %}
    <a href="{{ path('user_create') }}" class="btn btn-primary">
        创建用户
    </a>
{% endif %}

{% if is_granted('PERMISSION_ARTICLE_PUBLISH') %}
    <button onclick="publishArticle()">发布文章</button>
{% endif %}
```

### 权限管理

#### 创建角色和权限

```php
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;

// 创建角色
$editorRole = new Role();
$editorRole->setCode('ROLE_EDITOR');
$editorRole->setName('编辑员');
$editorRole->setDescription('内容编辑角色');

// 创建权限
$editPermission = new Permission();
$editPermission->setCode('PERMISSION_ARTICLE_EDIT');
$editPermission->setName('编辑文章');
$editPermission->setDescription('编辑文章内容的权限');

// 保存到数据库
$this->entityManager->persist($editorRole);
$this->entityManager->persist($editPermission);
$this->entityManager->flush();

// 为角色分配权限
$editorRole->addPermission($editPermission);
$this->entityManager->flush();
```

#### 为用户分配角色

```php
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;

// 分配角色给用户
$userRole = new UserRole();
$userRole->setUser($user);
$userRole->setRole($editorRole);

$this->entityManager->persist($userRole);
$this->entityManager->flush();
```

### CLI 命令

Bundle 提供了丰富的 CLI 命令来管理权限：

```bash
# 创建角色
php bin/console rbac:role:create ROLE_EDITOR "编辑员" "内容编辑角色"

# 创建权限
php bin/console rbac:permission:create PERMISSION_ARTICLE_EDIT "编辑文章" "编辑文章内容的权限"

# 授权
php bin/console rbac:grant ROLE_EDITOR PERMISSION_ARTICLE_EDIT

# 撤销权限
php bin/console rbac:revoke ROLE_EDITOR PERMISSION_ARTICLE_DELETE

# 查看用户权限
php bin/console rbac:user:permissions user@example.com

# 扫描代码中的权限使用情况
php bin/console rbac:scan-permissions src/ --check-missing

# 删除角色前检查依赖
php bin/console rbac:role:delete ROLE_EDITOR --check-dependencies
```

## 最佳实践

### 权限命名

权限命名采用 `PERMISSION_[模块]_[对象]_[操作]` 格式

建议模块：
- 用户相关：`PERMISSION_USER_*`
- 订单相关：`PERMISSION_ORDER_*`
- 文章相关：`PERMISSION_ARTICLE_*`
- 系统相关：`PERMISSION_SYSTEM_*`

示例：
- `PERMISSION_USER_CREATE` - 创建用户
- `PERMISSION_ORDER_VIEW` - 查看订单
- `PERMISSION_ARTICLE_PUBLISH` - 发布文章
- `PERMISSION_SYSTEM_CONFIG` - 系统配置

### 角色命名

角色命名采用 `ROLE_[名称]` 格式

示例：
- `ROLE_ADMIN` - 管理员
- `ROLE_MANAGER` - 经理
- `ROLE_EDITOR` - 编辑员
- `ROLE_USER` - 普通用户

### 数据模型

#### Role 角色模型

```php
class Role
{
    private int $id;                    // 主键ID
    private string $code;               // 角色代码（唯一）
    private string $name;               // 角色名称
    private ?string $description;       // 角色描述
    private ?int $parentRoleId;         // 父角色ID（支持层级）
    private ?int $hierarchyLevel;       // 层级深度（支持层级）
    private Collection $permissions;    // 关联权限
    private Collection $userRoles;      // 关联用户角色
}
```

#### Permission 权限模型

```php
class Permission
{
    private int $id;                    // 权限ID
    private string $code;               // 权限代码（唯一）
    private string $name;               // 权限名称
    private ?string $description;       // 权限描述
    private Collection $roles;          // 关联角色
}
```

#### UserRole 用户角色关联模型

```php
class UserRole
{
    private int $id;                    // 主键ID
    private UserInterface $user;        // 用户
    private Role $role;                 // 角色
}
```

## 高级功能

### 事件监听

Bundle 提供完整的事件系统，支持权限变更审计：

```php
use Tourze\RoleBasedAccessControlBundle\Event\RoleAssignedToUserEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class PermissionAuditListener
{
    #[AsEventListener(event: RoleAssignedToUserEvent::class)]
    public function onRoleAssigned(RoleAssignedToUserEvent $event): void
    {
        // 记录权限变更日志
        $this->logger->info('角色分配给用户', [
            'user' => $event->getUser()->getId(),
            'role' => $event->getRole()->getCode(),
            'operated_by' => $event->getOperatedBy()?->getId(),
        ]);
    }
}
```

### 自定义权限管理器

```php
use Tourze\RoleBasedAccessControlBundle\Service\PermissionManagerInterface;

class CustomPermissionManager implements PermissionManagerInterface
{
    public function getUserPermissions(UserInterface $user): array
    {
        // 自定义权限查询逻辑
    }

    public function hasPermission(UserInterface $user, string $permissionCode): bool
    {
        // 自定义权限检查逻辑
    }
}
```

## EasyAdmin 集成

Bundle 提供开箱即用的 EasyAdmin 管理界面：

```yaml
# config/easy_admin.yaml
easy_admin:
    entities:
        - Tourze\RoleBasedAccessControlBundle\Controller\Admin\RoleCrudController
        - Tourze\RoleBasedAccessControlBundle\Controller\Admin\PermissionCrudController
        - Tourze\RoleBasedAccessControlBundle\Controller\Admin\UserRoleCrudController
```

## 性能优化

### 权限查询优化

Bundle 提供高效的用户权限查询机制：

```php
// Repository 层优化查询
public function getUserPermissions(UserInterface $user): array
{
    return $this->createQueryBuilder('p')
        ->join('p.roles', 'r')
        ->join('r.userRoles', 'ur')
        ->where('ur.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();
}
```

### 批量权限检查

```php
// 批量检查多个权限，减少数据库查询
public function checkMultiplePermissions(UserInterface $user, array $permissionCodes): array
{
    $userPermissions = $this->getUserPermissionCodes($user);
    $results = [];

    foreach ($permissionCodes as $permissionCode) {
        $results[$permissionCode] = in_array($permissionCode, $userPermissions);
    }

    return $results;
}
```

## 测试

### 权限测试示例

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PermissionTest extends WebTestCase
{
    public function testPermissionCheck(): void
    {
        $client = static::createClient();

        // 模拟用户登录
        $user = $this->createUserWithRole('ROLE_EDITOR');
        $client->loginUser($user);

        // 测试有权限的页面
        $client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/admin/users/delete/1');
        $this->assertResponseStatusCodeSame(403); // 没有删除权限
    }
}
```

## 系统要求

- **PHP**: `^8.2`
- **Symfony**: `^7.3`
- **Doctrine ORM**: `^3.0`

## 开发计划

- 完善角色层级支持
- 权限继承功能
- 增强的权限审计日志
- 更好的 Symfony Security 集成

## 贡献指南

欢迎提交 Issue 和 Pull Request

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

## 许可证

本项目使用 MIT 许可证，详情请查看 [LICENSE](LICENSE) 文件

## 相关链接

- [Symfony Security 文档](https://symfony.com/doc/current/security.html)
- [EasyAdmin 文档](https://symfony.com/doc/current/bundles/EasyAdminBundle/index.html)
- [Doctrine ORM 文档](https://www.doctrine-project.org/projects/orm.html)

## 支持

如果您遇到问题，请：

- 提交 [GitHub Issue](https://github.com/tourze/php-monorepo/issues)
- 查看 [需求规格](.spec/requirements.md)
- 阅读 [开发指南](.spec/)