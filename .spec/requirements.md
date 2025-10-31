# Role-Based Access Control Bundle 需求规格书

## 概述

### 包的目的和价值主张
本包旨在为PHP Monorepo中的多个Bundle提供统一的基于角色的访问控制（RBAC）系统，解决权限管理分散、难以维护的核心痛点。通过与Symfony Security深度集成，复用原生#[IsGranted]注解，提供简洁的权限管理解决方案。

### 核心价值
- **统一管理**：集中管理分散在N个Bundle中的权限逻辑
- **原生集成**：完全复用Symfony Security的IsGranted注解和isGranted()方法
- **简洁设计**：只提供权限数据模型和Voter，无额外复杂功能

## 功能需求

### FR-001 权限数据模型
**普遍性需求**：
- 包必须提供Role实体，包含字段：code（唯一标识）、name（显示名称）、description、created_at、updated_at
- 包必须提供Permission实体，包含字段：code（唯一标识）、name（显示名称）、description、created_at、updated_at  
- 包必须提供UserRole实体用于用户-角色多对多关联
- 包必须在Role和Permission间建立多对多关系
- 包必须为Role.code和Permission.code建立全局唯一索引
- 包必须支持硬删除策略，删除前必须检查关联关系

**条件性需求**：
- 如果需要支持角色继承，包必须预留parent_role_id字段供将来扩展
- 如果需要支持角色层级权限计算，包必须预留hierarchy_level字段用于避免循环引用
- 如果需要记录角色层级关系的变更历史，包必须考虑角色继承的审计需求

### FR-002 权限检查服务
**普遍性需求**：
- 包必须提供PermissionManagerInterface用于权限管理
- 包必须提供获取用户所有权限的方法（单查询或两跳优化）
- 包必须提供检查用户是否具有指定权限的方法
- 包必须提供用户角色分配和撤销的幂等方法
- 包必须提供角色-权限关联管理的幂等方法

**事件驱动需求**：
- 当权限码不存在时，包必须记录警告日志并返回false
- 当执行权限变更操作时，包必须在事务中执行并保证数据一致性
- 当删除角色或权限时，包必须检查关联关系并阻止删除或返回清晰错误

### FR-003 Symfony Security集成（增强版）
**普遍性需求**：
- 包必须提供PermissionVoter继承AbstractVoter用于权限判断
- 包必须仅处理PERMISSION_*格式的权限属性
- 包必须对非目标属性返回ACCESS_ABSTAIN（保证与其他Voter共存）
- 包必须与Symfony原生#[IsGranted]注解完全兼容
- 包必须与isGranted()方法完全兼容
- 包必须确保权限与角色间关系的完整性，防止权限丢失或误用

**Voter判断逻辑增强**：
- 当权限码不存在时，包必须返回ACCESS_DENIED并记录详细日志（用户ID、权限码、时间戳）
- 当用户角色被删除但权限检查仍在进行时，包必须立即反映最新的权限状态
- 当存在角色-权限关系不一致时，包必须优先保证数据安全（拒绝访问）

**条件性需求**：
- 如果需要支持对象级权限，包必须预留subject参数处理逻辑
- 如果用户未认证，包必须委托给Symfony Security的标准流程
- 如果需要设置Voter优先级，包必须支持在Bundle配置中调整
- 如果需要支持资源级权限（如单个文档、项目），包必须预留扩展接口

### FR-004 权限管理接口
**普遍性需求**：
- 包必须提供角色和权限的CRUD操作
- 包必须提供角色-权限关联管理功能  
- 包必须提供用户-角色关联管理功能
- 包必须在删除前检查关联关系并返回可读错误信息
- 包必须支持角色和权限的重命名操作（修改name，保留code不变）

**事件驱动需求（增强版）**：
- 当角色分配给用户时，包必须发出RoleAssignedToUser事件，包含完整的操作上下文
- 当权限添加到角色时，包必须发出PermissionAddedToRole事件，包含操作者信息
- 当权限被撤销时，包必须发出PermissionRevokedFromRole事件，用于审计追踪
- 包必须支持事件的异步处理机制，便于与监控或审计系统集成
- 包必须为每个事件提供标准化的事件监听器接口

### FR-005 权限分散维护
**普遍性需求**：
- 包必须支持各Bundle自行维护权限码清单
- 包必须提供权限码命名规范以避免冲突
- 包必须支持通过前缀进行权限模块化管理
- 包必须提供权限码扫描工具验证代码中使用的权限是否已注册
- 包必须为开发者提供权限码共享机制的清晰文档，防止重复定义
- 包必须支持跨Bundle权限码依赖检查，确保权限引用的正确性

**模块化管理增强**：
- 包必须提供权限码冲突检测工具
- 包必须支持权限码的版本化管理（通过description字段记录变更）
- 包必须提供权限码使用情况统计功能

### FR-006 CLI管理工具（增强版）
**基础命令需求**：
- 包必须提供rbac:role命令用于角色管理（create/list/delete）
- 包必须提供rbac:permission命令用于权限管理（create/list/delete）
- 包必须提供rbac:grant和rbac:revoke命令用于权限授予和撤销
- 包必须提供rbac:user:permissions命令查看用户权限
- 包必须提供权限码扫描工具，检查代码中的PERMISSION_*是否已注册

**批量处理能力**：
- 包必须支持批量角色分配和撤销（通过CSV文件或JSON配置）
- 包必须支持批量权限授予和撤销操作
- 包必须提供批量导入/导出功能，便于环境间权限数据迁移
- 包必须支持批量操作的事务性，确保全部成功或全部回滚

**错误反馈增强**：
- 包必须在CLI操作中提供详细的执行报告（成功数量、失败数量、具体错误）
- 包必须在批量操作时提供进度条和实时反馈
- 包必须提供详细的失败原因分析和建议修复方案
- 包必须支持--verbose模式显示详细的调试信息
- 包必须在权限冲突时提供清晰的冲突解决建议

## 技术需求

### TR-001 数据持久化
**普遍性需求**：
- 包必须使用Doctrine ORM进行数据持久化
- 包必须提供Repository类用于优化的数据访问
- 包必须支持主流数据库（MySQL、PostgreSQL）
- 包必须建立必要的数据库索引以优化查询性能
- 包必须提供参考SQL schema和Seeder PHP脚本模板

**索引需求**：
- 包必须为user_role表创建(user_id, role_id)复合唯一索引
- 包必须为role_permission表创建(role_id, permission_id)复合唯一索引
- 包必须为Role.code和Permission.code创建唯一索引
- 包必须为查询频繁的字段创建单列索引

**明确排除**：
- 包不提供数据库Migration文件
- 包不处理数据库表创建和更新

### TR-002 审计与可追溯性（新增）
**审计日志详细要求**：
- 包必须记录所有权限变更操作的详细日志，包含：操作时间、操作用户、操作类型、目标对象、变更前后状态
- 包必须为每个关键操作分配唯一的操作ID，便于日志关联和问题追踪
- 包必须支持结构化日志格式（JSON），便于日志分析和监控系统集成
- 包必须记录权限检查的失败情况，包含用户标识、请求的权限码、失败原因、请求时间戳
- 包必须提供日志级别配置，支持在不同环境下调整日志详细程度

**可追溯性要求**：
- 包必须支持权限变更历史查询，能够回溯任意时间点的权限状态
- 包必须提供用户权限变更时间线功能
- 包必须支持通过操作ID关联相关的权限变更操作
- 包必须在数据库层面记录created_at和updated_at字段的精确时间戳

**审计接口**：
- 包必须提供AuditLoggerInterface供外部审计系统集成
- 包必须支持自定义审计策略（如敏感权限的特殊记录）
- 包必须提供审计日志的查询和导出接口

### TR-003 性能和可观测性
**普遍性需求**：
- 包必须提供单查询获取用户全部权限的Repository方法
- 包必须记录权限验证失败的警告日志（包含用户ID和权限码）
- 包必须为权限变更操作记录审计日志
- 包必须提供事件接口供外部系统接入审计

**条件性需求**：
- 如果调用方需要缓存，包必须建议在请求范围内使用Memory Cache
- 如果权限数据量大，包必须支持分页查询

### TR-004 框架集成
**普遍性需求**：
- 包必须提供Symfony Bundle结构用于服务注册
- 包必须与Doctrine ORM集成
- 包必须与Symfony Security组件集成
- 包必须支持Symfony事件分发器

**明确排除**：
- 包不提供内置缓存机制
- 包不支持YAML配置权限数据
- 包不提供自定义注解

## 非功能需求

### NFR-001 兼容性要求
**普遍性需求**：
- 包必须支持PHP 8.1+版本
- 包必须支持Symfony 6.4+版本
- 包必须兼容Doctrine ORM 2.15+

### NFR-002 设计原则
**普遍性需求**：
- 包必须遵循KISS原则，保持简洁
- 包必须遵循单一职责原则
- 包必须避免过度设计和不必要的抽象

## 集成需求

### IR-001 Symfony Security集成
**普遍性需求**：
- 包必须完全兼容现有Symfony Security配置
- 包必须不影响现有认证流程
- 包必须支持与其他Voter共存

### IR-002 使用方式
**普遍性需求**：
- 开发者必须能够使用#[IsGranted('PERMISSION_XXX')]注解
- 开发者必须能够使用$this->isGranted('PERMISSION_XXX')方法
- 开发者必须能够在Twig中使用is_granted('PERMISSION_XXX')函数

## 验收标准

### AS-001 测试覆盖
**普遍性要求**：
- 包必须达到≥90%的代码测试覆盖率
- 包必须包含单元测试和集成测试  
- 包必须通过PHPStan Level 8静态分析

### AS-002 核心功能测试场景
**正常链路测试**：
- 单角色用户权限检查
- 多角色用户权限去重验证
- #[IsGranted]、isGranted()、is_granted()三端一致性
- 硬删除策略下的数据一致性测试

**异常链路测试**：
- 未知权限码处理（记录日志并返回false）
- 并发授予/撤销操作的幂等性验证
- 删除前关联检查和错误信息验证
- 硬删除操作的数据完整性验证

**集成测试**：
- PermissionVoter与其他Voter共存验证
- ACCESS_ABSTAIN行为正确性测试
- Symfony Security流程不受影响验证
- 事务回滚和数据一致性测试

### AS-003 性能基准和自动化测试
**性能基准要求**：
- 权限查询单次响应时间≤50ms（无缓存条件下）
- 1000个权限数据的内存使用≤10MB
- 支持≥50并发用户的权限检查
- 批量权限操作（100个用户）完成时间≤5秒

**自动化性能测试**：
- 包必须提供自动化性能基准测试套件
- 包必须在CI/CD流程中集成性能回归测试
- 包必须在高并发场景下验证权限查询的性能表现
- 包必须提供性能监控指标收集接口，便于APM系统集成
- 包必须测试在大数据量情况下（10万用户、1万角色、10万权限）的性能表现

### AS-004 CLI工具验证
**功能验证**：
- 所有rbac:*命令的正确执行
- 权限码扫描工具的准确性验证
- 批量操作的事务性和完整性验证
- CLI错误处理和用户体验测试

**批量操作测试**：
- 批量导入/导出功能的数据完整性验证
- 大批量操作（1000+记录）的性能和稳定性测试
- 批量操作中断恢复机制的验证
- 并发CLI操作的安全性测试

### AS-005 数据完整性验证
**约束验证**：
- 唯一索引约束的正确性
- 硬删除前置检查的完整性
- 关联关系的完整性验证

### AS-006 API文档和开发者体验
**API文档要求**：
- 包必须为所有公共接口提供完整的PHPDoc注释
- 包必须提供API使用示例和最佳实践文档
- 包必须明确API方法的参数类型、返回值和可能抛出的异常
- 包必须提供不同Symfony版本的兼容性说明
- 包必须提供完整的集成指南，包含常见问题解决方案

**开发者友好性**：
- 包必须提供清晰的错误消息，包含问题原因和建议解决方案
- 包必须支持IDE的自动补全和类型提示
- 包必须提供调试模式，便于开发阶段排查问题
- 包必须在异常情况下提供足够的上下文信息

## 约束条件

### 功能约束
- 不实现权限缓存机制（建议调用方实现请求级缓存）
- 不提供数据库Migration（提供参考Schema）
- 不支持YAML权限配置（分散在各Bundle代码中管理）
- 不提供自定义注解（复用Symfony标准注解）
- 不实现权限继承（保持扁平结构，预留扩展点）
- 不支持动态权限计算（静态权限检查）
- 不支持多租户（全局唯一权限体系）
- 使用硬删除策略（删除前强制检查关联）

### 技术约束
- 必须遵循Monorepo的包开发规范
- 必须使用0.0.*版本号表示开发状态
- 不得创建YAML配置文件
- 不得修改现有Bundle的核心逻辑
- 所有写操作必须在事务中执行
- 所有管理方法必须实现幂等性

## ✅ 最终确认的关键决策

### 已确定的架构决策：
1. **多租户策略**：不支持多租户，全局唯一权限体系
2. **权限治理方式**：分散维护，各Bundle自行管理权限码
3. **删除策略**：硬删除，删除前强制检查关联关系
4. **角色层级**：扁平结构，暂不支持继承（预留扩展点）

### 核心设计原则：
- **KISS原则**：简洁实用，避免过度设计
- **分散治理**：权限码由各Bundle维护，通过前缀规范避免冲突
- **硬删除**：数据完整性优先，删除前严格检查
- **可扩展**：预留关键扩展点，支持未来演进

---

*基于明确决策的最终需求规格书，专注于可落地的工程化实现。*

## 命名规范与约束

### 权限码命名规范
- 格式：`PERMISSION_[MODULE]_[OBJECT]_[ACTION]`
- 示例：`PERMISSION_USER_EDIT`, `PERMISSION_ORDER_VIEW`, `PERMISSION_ARTICLE_PUBLISH`
- 全部大写，使用下划线分隔
- 动词在最后（CREATE/READ/UPDATE/DELETE/VIEW/EDIT/PUBLISH等）

### 角色码命名规范  
- 格式：`ROLE_[NAME]`
- 示例：`ROLE_ADMIN`, `ROLE_MANAGER`, `ROLE_USER`
- 全部大写，使用下划线分隔
- 避免使用动词，专注于身份/职能

### 前缀规范建议
- 用户模块：`PERMISSION_USER_*`
- 订单模块：`PERMISSION_ORDER_*`  
- 文章模块：`PERMISSION_ARTICLE_*`
- 系统模块：`PERMISSION_SYSTEM_*`

---

*请在进入设计阶段前确认上述4个关键决策点，以确保需求的完整性和可实施性。*

## 核心API设计示例

### 基础使用示例
```php
// Controller中使用原生注解
#[IsGranted('PERMISSION_USER_EDIT')]
public function editUser(): Response { ... }

// Service中使用权限检查
if ($this->security->isGranted('PERMISSION_USER_EDIT')) {
    // 执行操作
}

// Twig模板中使用
{% if is_granted('PERMISSION_USER_EDIT') %}
    <button>编辑用户</button>
{% endif %}
```

### 权限管理API示例
```php
interface PermissionManagerInterface 
{
    // 幂等操作，返回是否发生变更
    public function assignRoleToUser(UserInterface $user, string $roleCode): bool;
    public function revokeRoleFromUser(UserInterface $user, string $roleCode): bool;
    public function addPermissionToRole(string $roleCode, string $permissionCode): bool;
    public function removePermissionFromRole(string $roleCode, string $permissionCode): bool;
    
    // 查询方法（优化的单查询实现）
    public function getUserPermissions(UserInterface $user): array;
    public function hasPermission(UserInterface $user, string $permissionCode): bool;
    public function getUserRoles(UserInterface $user): array;
    
    // 硬删除前置检查
    public function canDeleteRole(string $roleCode): bool;
    public function canDeletePermission(string $permissionCode): bool;
    public function deleteRole(string $roleCode): void; // 可能抛出异常
    public function deletePermission(string $permissionCode): void; // 可能抛出异常
    
    // 批量操作支持
    public function bulkAssignRoles(array $userRoleMapping): BulkOperationResult;
    public function bulkRevokeRoles(array $userRoleMapping): BulkOperationResult;
    public function bulkGrantPermissions(array $rolePermissionMapping): BulkOperationResult;
}

// 批量操作结果
class BulkOperationResult
{
    public function getSuccessCount(): int;
    public function getFailureCount(): int;
    public function getFailures(): array; // 详细的失败信息
    public function isFullSuccess(): bool;
}
```

### CLI命令增强示例
```bash
# 批量操作
php bin/console rbac:bulk:assign-roles users.csv --dry-run
php bin/console rbac:bulk:grant-permissions roles_permissions.json --progress

# 详细报告
php bin/console rbac:role:delete ROLE_EDITOR --check-dependencies --verbose
php bin/console rbac:audit:export --from="2024-01-01" --format=json
```

### 增强的事件系统示例
```php
// 权限变更事件（增强版）
class RoleAssignedToUserEvent
{
    public function __construct(
        private UserInterface $user,
        private Role $role,
        private \DateTimeImmutable $occurredAt,
        private ?UserInterface $operatedBy = null, // 操作者
        private string $operationId = '', // 操作ID
        private array $context = [] // 额外上下文
    ) {}
}

// 事件监听器（支持异步）
class PermissionAuditListener
{
    public function onRoleAssigned(RoleAssignedToUserEvent $event): void
    {
        // 结构化日志
        $this->auditLogger->info('role.assigned', [
            'operation_id' => $event->getOperationId(),
            'user_id' => $event->getUser()->getId(),
            'role_code' => $event->getRole()->getCode(),
            'operated_by' => $event->getOperatedBy()?->getId(),
            'occurred_at' => $event->getOccurredAt()->format('c'),
            'context' => $event->getContext()
        ]);
        
        // 异步处理
        $this->messageBus->dispatch(new AuditNotificationMessage($event));
    }
}

// 审计接口
interface AuditLoggerInterface
{
    public function logPermissionChange(string $action, array $data): void;
    public function logPermissionCheck(string $permissionCode, UserInterface $user, bool $result): void;
    public function generateOperationId(): string;
}
```

### CLI命令使用示例
```bash
# 创建角色和权限
php bin/console rbac:role:create ROLE_EDITOR "内容编辑者" "负责内容编辑的角色"
php bin/console rbac:permission:create PERMISSION_ARTICLE_EDIT "编辑文章" "编辑文章内容的权限"

# 权限授予和管理
php bin/console rbac:grant ROLE_EDITOR PERMISSION_ARTICLE_EDIT
php bin/console rbac:revoke ROLE_EDITOR PERMISSION_ARTICLE_DELETE

# 删除角色和权限（带前置检查）
php bin/console rbac:role:delete ROLE_EDITOR --force
php bin/console rbac:permission:delete PERMISSION_ARTICLE_EDIT --check-usage

# 查看和扫描
php bin/console rbac:user:permissions user@example.com  
php bin/console rbac:scan-permissions src/ --check-missing --by-module
```

### 优化的Repository示例
```php
class UserPermissionRepository
{
    // 优化的单查询获取用户全部权限
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
    
    // 批量权限检查（减少数据库查询）
    public function checkMultiplePermissions(UserInterface $user, array $permissionCodes): array
    {
        $results = [];
        $userPermissions = $this->getUserPermissionCodes($user);
        
        foreach ($permissionCodes as $permissionCode) {
            $results[$permissionCode] = in_array($permissionCode, $userPermissions);
        }
        
        return $results;
    }
}
```

---

*本需求规格书已根据专业工程化建议全面增强，涵盖角色继承预留、Voter逻辑优化、CLI批量处理、详细审计、事件驱动架构、性能自动化测试等企业级生产需求。*