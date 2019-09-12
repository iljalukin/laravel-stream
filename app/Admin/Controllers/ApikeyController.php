<?php

namespace App\Admin\Controllers;

use App\Models\Apikey;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Auth\Permission;

class ApikeyController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Index')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        if($this->form()->edit($id)->model()->uid != Admin::user()->id)
        {
            abort(404);
        }

        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Apikey);

        $grid->model()->where('uid', '=', Admin::user()->id);
        $grid->id('ID')->sortable();
        $grid->name('Name')->editable();
        $grid->key('Key');
        $grid->url('URL');
        $grid->created_at('Created at')->sortable();
        $grid->updated_at('Updated at')->sortable();

        $grid->filter(function ($filter) {

            // Sets the range query for the created_at field
            $filter->contains('name');
            $filter->contains('key');
            $filter->between('created_at', 'Created Time')->datetime();
            $filter->between('updated_at', 'Updated Time')->datetime();

        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Apikey::where('id', $id)->where('uid', Admin::user()->id)->firstOrFail());

        $show->name('ID');
        $show->name('Name');
        $show->key('Key');
        $show->url('URL');
        $show->created_at('Created at');
        $show->updated_at('Updated at');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Apikey);

        $form->display('id','ID');
        $form->hidden('uid')->default(Admin::user()->id);
        $form->text('name','Name');
        $form->text('key', 'Key');
        $form->text('url', 'URL');
        $form->display('created_at','Created at');
        $form->display('updated_at','Updated at');

        return $form;
    }
}
