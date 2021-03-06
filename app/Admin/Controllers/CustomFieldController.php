<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\RowAction\CustomFieldDeleteAction;
use App\Admin\Grid\Displayers\RowActions;
use App\Admin\Repositories\CustomField;
use App\Support\Data;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Pour\Plus\LaravelAdmin;


class CustomFieldController extends AdminController
{
    public function index(Content $content): Content
    {
        return $content
            ->title($this->title())
            ->description(admin_trans_label('description'))
            ->body($this->grid());
    }

    public function title()
    {
        return admin_trans_label('title');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        return Grid::make(new CustomField(), function (Grid $grid) {
            $grid->column('id');
            $grid->column('table_name')->using(Data::itemNameByTableName());
            $grid->column('name');
            $grid->column('nick_name');
            $grid->column('type');
            $grid->column('is_nullable')->using(LaravelAdmin::yesOrNo());

            $grid->toolsWithOutline(false);
            $grid->enableDialogCreate();
            $grid->disableEditButton();
            $grid->disableDeleteButton();

            $grid->actions(function (RowActions $actions) {
                $actions->append(new CustomFieldDeleteAction());
            });

            $grid->filter(function ($filter) {
                $filter->panel();
                $filter->equal('table_name')->select(Data::itemNameByTableName());
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id): Show
    {
        return Show::make($id, new CustomField(), function (Show $show) {
            $show->field('id');
            $show->field('table_name')->using(Data::itemNameByTableName());
            $show->field('name');
            $show->field('nick_name');
            $show->field('type');
            $show->field('is_nullable')->using(LaravelAdmin::yesOrNo());

            $show->disableEditButton();
            $show->disableDeleteButton();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        return Form::make(new CustomField(), function (Form $form) {
            $form->display('id');
            $form->select('table_name')
                ->options(Data::itemNameByTableName())
                ->required();
            $form->text('name')
                ->help(admin_trans_label('Name Help'))
                ->required();
            $form->text('nick_name')
                ->help(admin_trans_label('Nick Name Help'))
                ->required();
            $form->select('type')->required()
                ->options(Data::customFieldTypes());
            $form->radio('is_nullable')
                ->options(LaravelAdmin::yesOrNo())
                ->help(admin_trans_label('Is Nullable Help'))
                ->default(0);

            /*
             * 如果直接执行表单自带保存，会报错 There is no active transaction
             * 虽然是可以创建成功，但是页面会提示错误，对用户不友好
             * 因此这里需要自定义保存，来“隐藏”报错
             */
            $form->saving(function (Form $form) {
                $exist = \App\Models\CustomField::where('table_name', $form->input('table_name'))
                    ->where('name', $form->input('name'))
                    ->first();
                if (!empty($exist)) {
                    return $form->response()
                        ->error(trans('main.record_same'));
                }
                $custom_fields = new \App\Models\CustomField();
                $custom_fields->table_name = $form->input('table_name');
                $custom_fields->name = $form->input('name');
                $custom_fields->nick_name = $form->input('nick_name');
                $custom_fields->type = $form->input('type');
                $custom_fields->is_nullable = $form->input('is_nullable');
                $custom_fields->save();
                return $form->response()
                    ->location(admin_route('custom_fields.index'));
            });

            $form->disableDeleteButton();
        });
    }
}
