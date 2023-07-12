<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

use think\facade\Route;
use app\common\middleware\AdminAuthMiddleware;
use app\common\middleware\AdminTokenMiddleware;
use app\common\middleware\AllowOriginMiddleware;
use app\common\middleware\LogMiddleware;

Route::group(function () {

    //文章分类
    Route::group('system/article/category', function () {
        Route::get('create/form', '/createForm')->name('systemArticleCategoryCreateForm')->option([
            '_alias' => '文章分类添加表单',
            '_auth' => false,
            '_form' => 'systemArticleCategoryCreate',
        ]);
        Route::get('lst', '/lst')->name('systemArticleCategoryLst')->option([
            '_alias' => '文章分类列表',
        ]);
        Route::post('create', '/create')->name('systemArticleCategoryCreate')->option([
            '_alias' => '文章分类添加',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('systemArticleCategoryUpdateForm')->option([
            '_alias' => '文章分类编辑表单',
            '_auth' => false,
            '_form' => 'systemArticleCategoryUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemArticleCategoryUpdate')->option([
            '_alias' => '文章分类编辑',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemArticleCategoryStatus')->option([
            '_alias' => '文章分类修改状态',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemArticleCategoryDelete')->option([
            '_alias' => '文章分类删除',
        ]);
        Route::get('detail/:id', '/detail')->name('systemArticleCategoryDetail')->option([
            '_alias' => '文章分类详情',
        ]);
        Route::get('select', '/select')->option([
            '_alias' => '文章分类筛选',
            '_auth'  => false,
        ]);
    })->prefix('admin.article.ArticleCategory')->option([
        '_path' => '/cms/articleCategory',
        '_auth' => true,
    ]);

    //文章
    Route::group('system/article/article', function () {
        Route::get('lst', '/getList')->name('systemArticlArticleLst')->option([
            '_alias' => '文章列表',
        ]);
        Route::post('create', '/create')->name('systemArticleArticleCreate')->option([
            '_alias' => '文章添加',
        ]);
        Route::post('update/:id', '/update')->name('systemArticArticleleUpdate')->option([
            '_alias' => '文章编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemArticArticleleDelete')->option([
            '_alias' => '文章删除',
        ]);
        Route::get('detail/:id', '/detail')->name('systemArticArticleleDetail')->option([
            '_alias' => '文章详情',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemArticlArticlStatus')->option([
            '_alias' => '文章修改状态',
        ]);
    })->prefix('admin.article.Article')->option([
        '_path' => '/cms/article',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/cms/article',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/cms/article',
                '_alias' => '素材列表',
                '_auth'  => true,
            ],
        ]
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
