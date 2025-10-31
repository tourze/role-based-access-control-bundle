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
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;

/**
 * 角色管理控制器
 *
 * @extends AbstractCrudController<Role>
 */
#[AdminCrud(routePath: '/rbac/role', routeName: 'rbac_role')]
final class RoleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Role::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('角色')
            ->setEntityLabelInPlural('角色管理')
            ->setPageTitle('index', '角色列表')
            ->setPageTitle('detail', '角色详情')
            ->setPageTitle('new', '新建角色')
            ->setPageTitle('edit', '编辑角色')
            ->setHelp('index', '管理系统中的所有角色及其权限分配')
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

        yield TextField::new('code', '角色编码')
            ->setRequired(true)
            ->setHelp('角色的唯一标识符，如：ROLE_ADMIN')
            ->setMaxLength(255)
        ;

        yield TextField::new('name', '角色名称')
            ->setRequired(true)
            ->setHelp('角色的显示名称，如：系统管理员')
            ->setMaxLength(255)
        ;

        yield TextareaField::new('description', '角色描述')
            ->setRequired(false)
            ->setHelp('角色的详细描述信息')
            ->setMaxLength(1000)
            ->hideOnIndex()
        ;

        yield IntegerField::new('parentRoleId', '父角色ID')
            ->setRequired(false)
            ->setHelp('父角色的ID，用于构建角色层级关系')
            ->hideOnIndex()
        ;

        yield IntegerField::new('hierarchyLevel', '层级深度')
            ->setRequired(false)
            ->setHelp('角色在层级结构中的深度，0为顶级角色')
            ->hideOnIndex()
        ;

        yield AssociationField::new('permissions', '关联权限')
            ->setHelp('该角色拥有的权限列表')
            ->hideOnIndex()
            ->autocomplete()
        ;

        yield AssociationField::new('userRoles', '关联用户')
            ->setHelp('拥有该角色的用户列表')
            ->hideOnIndex()
            ->hideOnForm()
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
            ->add(TextFilter::new('code', '角色编码'))
            ->add(TextFilter::new('name', '角色名称'))
            ->add(NumericFilter::new('parentRoleId', '父角色ID'))
            ->add(NumericFilter::new('hierarchyLevel', '层级深度'))
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

    public function createEntity(string $entityFqcn): Role
    {
        return new Role();
    }
}
