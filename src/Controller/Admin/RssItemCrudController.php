<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\RSSFeedCollectBundle\Entity\RssFeed;
use Tourze\RSSFeedCollectBundle\Entity\RssItem;

#[AdminCrud(routePath: '/rss-feed-collect/rss-item', routeName: 'rss_feed_collect_rss_item')]
final class RssItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RssItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('RSS文章')
            ->setEntityLabelInPlural('RSS文章列表')
            ->setPageTitle('index', 'RSS文章管理')
            ->setPageTitle('new', '添加RSS文章')
            ->setPageTitle('edit', '编辑RSS文章')
            ->setPageTitle('detail', 'RSS文章详情')
            ->setHelp('index', '查看和管理从RSS源抓取的文章内容')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'title', 'link', 'guid'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->setColumns(2)
        ;

        yield TextField::new('title', '文章标题')
            ->setColumns(8)
            ->setHelp('RSS文章的标题')
        ;

        yield UrlField::new('link', '文章链接')
            ->setColumns(8)
            ->setHelp('文章的原始链接地址，用于去重')
        ;

        yield AssociationField::new('rssFeed', '所属RSS源')
            ->setColumns(4)
            ->setHelp('该文章来源的RSS源')
        ;

        yield TextField::new('guid', 'RSS GUID')
            ->setColumns(8)
            ->hideOnIndex()
            ->setHelp('RSS文章的唯一标识符')
        ;

        yield TextareaField::new('description', '文章摘要')
            ->setColumns(12)
            ->setNumOfRows(3)
            ->hideOnIndex()
            ->setHelp('文章的摘要或简短描述')
        ;

        yield TextareaField::new('content', '文章内容')
            ->setColumns(12)
            ->setNumOfRows(8)
            ->hideOnIndex()
            ->setHelp('文章的完整内容')
            ->formatValue(function ($value) {
                if (null === $value) {
                    return '';
                }

                $truncated = mb_substr($value, 0, 200);
                if (mb_strlen($value) > 200) {
                    $truncated .= '...';
                }

                return '<div class="text-muted" style="max-height: 100px; overflow: hidden;">' .
                       nl2br(htmlspecialchars($truncated)) . '</div>';
            })
        ;

        yield DateTimeField::new('publishTime', '发布时间')
            ->setColumns(4)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('文章在RSS源中的发布时间')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setColumns(4)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setColumns(4)
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
            ->add(TextFilter::new('title', '文章标题'))
            ->add(EntityFilter::new('rssFeed', '所属RSS源'))
            ->add(DateTimeFilter::new('publishTime', '发布时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
