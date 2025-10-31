<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;

#[AdminCrud(routePath: '/rss-feed-collect/rss-feed', routeName: 'rss_feed_collect_rss_feed')]
final class RssFeedCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RssFeed::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('RSS源')
            ->setEntityLabelInPlural('RSS源列表')
            ->setPageTitle('index', 'RSS源管理')
            ->setPageTitle('new', '添加RSS源')
            ->setPageTitle('edit', '编辑RSS源')
            ->setPageTitle('detail', 'RSS源详情')
            ->setHelp('index', '管理RSS源的抓取配置和状态监控')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'name', 'url', 'category', 'description'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->setColumns(2)
        ;

        yield TextField::new('name', 'RSS源名称')
            ->setColumns(6)
            ->setHelp('给RSS源起一个便于识别的名称')
        ;

        yield UrlField::new('url', 'RSS源URL')
            ->setColumns(6)
            ->setHelp('RSS源的完整URL地址')
        ;

        yield TextareaField::new('description', '描述信息')
            ->setColumns(12)
            ->setNumOfRows(3)
            ->hideOnIndex()
        ;

        yield TextField::new('category', '分类')
            ->setColumns(4)
            ->setHelp('RSS源的分类，便于管理和筛选')
        ;

        yield BooleanField::new('isActive', '激活状态')
            ->setColumns(2)
            ->setHelp('是否启用该RSS源的抓取')
        ;

        yield ChoiceField::new('status', '状态')
            ->setChoices([
                '正常' => 'active',
                '错误' => 'error',
                '禁用' => 'disabled',
            ])
            ->setColumns(3)
            ->formatValue(function ($value) {
                $labels = [
                    'active' => '<span class="badge badge-success">正常</span>',
                    'error' => '<span class="badge badge-danger">错误</span>',
                    'disabled' => '<span class="badge badge-secondary">禁用</span>',
                ];

                return $labels[$value] ?? $value;
            })
        ;

        yield IntegerField::new('collectIntervalMinutes', '抓取间隔(分钟)')
            ->setColumns(3)
            ->setHelp('RSS源的抓取间隔，单位：分钟')
        ;

        yield IntegerField::new('itemsCount', '文章总数')
            ->hideOnForm()
            ->setColumns(2)
        ;

        yield DateTimeField::new('lastCollectTime', '最后抓取时间')
            ->hideOnForm()
            ->setColumns(3)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield TextareaField::new('lastError', '最后错误信息')
            ->hideOnIndex()
            ->onlyOnForms()
            ->setColumns(12)
            ->setNumOfRows(3)
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setColumns(3)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setColumns(3)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, 'detail')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'RSS源名称'))
            ->add(TextFilter::new('category', '分类'))
            ->add(BooleanFilter::new('isActive', '激活状态'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices([
                '正常' => 'active',
                '错误' => 'error',
                '禁用' => 'disabled',
            ]))
            ->add(DateTimeFilter::new('lastCollectTime', '最后抓取时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
