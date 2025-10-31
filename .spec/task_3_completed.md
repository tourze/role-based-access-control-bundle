# Task 3 Completed: 异常类定义

## 实施摘要
成功创建了RBAC专用的异常类体系，提供不同错误场景的精确处理和上下文信息。

## 已完成的功能
1. **RoleNotFoundException** - 角色不存在异常，继承InvalidArgumentException
   - 包含roleCode上下文信息
   - 提供forRoleCode()工厂方法
   - 生成描述性错误消息

2. **PermissionNotFoundException** - 权限不存在异常，继承InvalidArgumentException
   - 包含permissionCode上下文信息
   - 提供forPermissionCode()工厂方法
   - 生成描述性错误消息

3. **DeletionConflictException** - 删除冲突异常，继承RuntimeException
   - 包含entityIdentifier和affectedEntities信息
   - 提供forRoleDeletion()和forPermissionDeletion()工厂方法
   - 生成包含受影响实体数量的详细消息

## 质量检查结果
- ✅ PHPStan: 0错误
- ✅ PHPUnit: 16/16 异常测试通过
- ✅ 代码规范: 符合项目标准
- ✅ 测试覆盖: 异常层100%覆盖

## 技术决策
- 选择合适的基类（InvalidArgumentException vs RuntimeException）
- 使用工厂方法提供语义化的异常创建
- 包含丰富的上下文信息用于调试和错误处理
- 遵循严格类型声明和现代PHP特性

## 文件变更
- 创建: `src/Exception/RoleNotFoundException.php` - 角色未找到异常
- 创建: `src/Exception/PermissionNotFoundException.php` - 权限未找到异常
- 创建: `src/Exception/DeletionConflictException.php` - 删除冲突异常
- 创建: `tests/Exception/ExceptionHierarchyTest.php` - 异常体系综合测试
- 创建: `tests/Exception/RoleNotFoundExceptionTest.php` - 角色异常专项测试
- 创建: `tests/Exception/PermissionNotFoundExceptionTest.php` - 权限异常专项测试
- 创建: `tests/Exception/DeletionConflictExceptionTest.php` - 删除冲突异常专项测试

## 下一步
继续任务4: PHPUnit测试配置