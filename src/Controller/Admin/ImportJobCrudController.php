<?php

declare(strict_types=1);

namespace Tourze\RSSFeedCollectBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\RSSFeedCollectBundle\Entity\ImportJob;

#[AdminCrud(routePath: '/rss-feed-collect/import-job', routeName: 'rss_feed_collect_import_job')]
final class ImportJobCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ImportJob::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('导入任务')
            ->setEntityLabelInPlural('导入任务列表')
            ->setPageTitle('index', '导入任务管理')
            ->setPageTitle('new', '创建导入任务')
            ->setPageTitle('edit', '编辑导入任务')
            ->setPageTitle('detail', '导入任务详情')
            ->setHelp('index', '管理OPML文件导入任务的执行状态和进度')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'fileName'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->setColumns(2)
        ;

        yield TextField::new('fileName', '文件名称')
            ->setColumns(6)
            ->setHelp('导入的OPML文件名称')
        ;

        yield ChoiceField::new('status', '任务状态')
            ->setChoices([
                '待处理' => ImportJob::STATUS_PENDING,
                '处理中' => ImportJob::STATUS_PROCESSING,
                '已完成' => ImportJob::STATUS_COMPLETED,
                '失败' => ImportJob::STATUS_FAILED,
            ])
            ->setColumns(3)
            ->formatValue(function ($value) {
                $labels = [
                    ImportJob::STATUS_PENDING => '<span class="badge badge-warning">待处理</span>',
                    ImportJob::STATUS_PROCESSING => '<span class="badge badge-info">处理中</span>',
                    ImportJob::STATUS_COMPLETED => '<span class="badge badge-success">已完成</span>',
                    ImportJob::STATUS_FAILED => '<span class="badge badge-danger">失败</span>',
                ];

                return $labels[$value] ?? $value;
            })
        ;

        yield IntegerField::new('totalItems', '总项目数')
            ->setColumns(3)
            ->setHelp('OPML文件中的RSS源总数')
        ;

        yield IntegerField::new('processedItems', '已处理项目数')
            ->setColumns(3)
            ->setHelp('已经处理的RSS源数量')
        ;

        yield IntegerField::new('successfulItems', '成功项目数')
            ->setColumns(3)
            ->setHelp('成功导入的RSS源数量')
        ;

        yield IntegerField::new('failedItems', '失败项目数')
            ->setColumns(3)
            ->setHelp('导入失败的RSS源数量')
        ;

        yield PercentField::new('progressPercentage', '完成进度')
            ->hideOnForm()
            ->setColumns(3)
            ->formatValue(function ($value) {
                $percentage = round($value, 1);
                $progressClass = $percentage >= 100 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-info');

                return sprintf(
                    '<div class="progress" style="height: 20px;">
                        <div class="progress-bar %s" role="progressbar" style="width: %s%%" aria-valuenow="%s" aria-valuemin="0" aria-valuemax="100">
                            %s%%
                        </div>
                    </div>',
                    $progressClass,
                    $percentage,
                    $percentage,
                    $percentage
                );
            })
        ;

        yield CodeEditorField::new('errors', '错误信息')
            ->setLanguage('javascript')
            ->setColumns(12)
            ->setNumOfRows(6)
            ->hideOnIndex()
            ->setHelp('导入过程中出现的错误详情(JSON格式)')
            ->formatValue(function ($value) {
                if (null === $value || [] === $value) {
                    return '';
                }

                $jsonString = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                if (false === $jsonString) {
                    return '<pre class="text-danger">JSON编码失败</pre>';
                }

                return '<pre class="text-danger">' . htmlspecialchars($jsonString) . '</pre>';
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setColumns(4)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('completeTime', '完成时间')
            ->hideOnForm()
            ->setColumns(4)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('任务完成的时间，未完成则为空')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, 'detail')
            ->disable('new', 'edit')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('fileName', '文件名称'))
            ->add(ChoiceFilter::new('status', '任务状态')->setChoices([
                '待处理' => ImportJob::STATUS_PENDING,
                '处理中' => ImportJob::STATUS_PROCESSING,
                '已完成' => ImportJob::STATUS_COMPLETED,
                '失败' => ImportJob::STATUS_FAILED,
            ]))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('completeTime', '完成时间'))
        ;
    }
}
