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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\RoleBasedAccessControlBundle\Entity\Role;
use Tourze\RoleBasedAccessControlBundle\Entity\UserRole;

/**
 * 用户角色关联管理控制器
 *
 * @extends AbstractCrudController<UserRole>
 */
#[AdminCrud(routePath: '/rbac/user-role', routeName: 'rbac_user_role')]
final class UserRoleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserRole::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('用户角色关联')
            ->setEntityLabelInPlural('用户角色关联管理')
            ->setPageTitle('index', '用户角色关联列表')
            ->setPageTitle('detail', '用户角色关联详情')
            ->setPageTitle('new', '新建用户角色关联')
            ->setPageTitle('edit', '编辑用户角色关联')
            ->setHelp('index', '管理用户与角色之间的关联关系')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['userId'])
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

        yield TextField::new('userId', '用户ID')
            ->setRequired(true)
            ->setHelp('用户的唯一标识符')
            ->setMaxLength(255)
        ;

        yield AssociationField::new('role', '角色')
            ->setRequired(true)
            ->setHelp('分配给用户的角色')
            ->autocomplete()
        ;

        yield DateTimeField::new('assignTime', '分配时间')
            ->setRequired(true)
            ->setHelp('角色分配给用户的时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('userId', '用户ID'))
            ->add(EntityFilter::new('role', '角色'))
            ->add(DateTimeFilter::new('assignTime', '分配时间'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function createEntity(string $entityFqcn): UserRole
    {
        return new UserRole();
    }
}
