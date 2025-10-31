<?php

declare(strict_types=1);

namespace Tourze\RoleBasedAccessControlBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\RoleBasedAccessControlBundle\Entity\Permission;

/**
 * 权限管理控制器
 *
 * @extends AbstractCrudController<Permission>
 */
#[AdminCrud(routePath: '/rbac/permission', routeName: 'rbac_permission')]
final class PermissionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Permission::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('权限')
            ->setEntityLabelInPlural('权限管理')
            ->setPageTitle('index', '权限列表')
            ->setPageTitle('detail', '权限详情')
            ->setPageTitle('new', '新建权限')
            ->setPageTitle('edit', '编辑权限')
            ->setHelp('index', '管理系统中的所有权限及其分配给的角色')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['code', 'name', 'description'])
            ->setPaginatorPageSize(20)
        ;
    }

    /**
     * @return iterable<FieldInterface|string>
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('code', '权限代码')
            ->setRequired(true)
            ->setHelp('权限的唯一标识符，必须以PERMISSION_开头，如：PERMISSION_USER_EDIT')
            ->setMaxLength(255)
        ;

        yield TextField::new('name', '权限名称')
            ->setRequired(true)
            ->setHelp('权限的显示名称，如：编辑用户')
            ->setMaxLength(255)
        ;

        yield TextareaField::new('description', '权限描述')
            ->setRequired(false)
            ->setHelp('权限的详细描述信息')
            ->setMaxLength(65535)
            ->hideOnIndex()
        ;

        yield AssociationField::new('roles', '关联角色')
            ->setHelp('拥有该权限的角色列表')
            ->hideOnIndex()
            ->autocomplete()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('code', '权限代码'))
            ->add(TextFilter::new('name', '权限名称'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function createEntity(string $entityFqcn): Permission
    {
        return new Permission();
    }
}
