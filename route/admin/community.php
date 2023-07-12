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

    //社区文章
    Route::group('community', function () {
        Route::get('lst', '/lst')->name('systemCommunityLst')->option([
            '_alias' => '文章列表',
        ]);
        Route::get('detail/:id', '/detail')->name('systemCommunityDetail')->option([
            '_alias' => '文章详情',
        ]);
        Route::get('update/:id/form', '/updateForm')->name('systemCommunityUpdateForm')->option([
            '_alias' => '文章编辑表单',
            '_auth' => false,
            '_form' => 'systemCommunityUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemCommunityUpdate')->option([
            '_alias' => '文章编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemCommunityDelete')->option([
            '_alias' => '文章删除',
        ]);
        Route::get('status/:id/form', '/showForm')->name('systemCommunityStatusForm')->option([
            '_alias' => '修改状态表单',
            '_auth' => false,
            '_form' => 'systemCommunityStatus',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemCommunityStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('show/:id', '/switchShow')->name('systemCommunityShow')->option([
            '_alias' => '文章详情',
        ]);
        Route::get('title', '/title')->name('systemCommunityTitle')->option([
            '_alias' => '统计',
        ]);
    })->prefix('admin.community.Community')->option([
        '_path' => '/community/list',
        '_auth' => true,
    ]);

    //社区分类
    Route::group('community/category', function () {
        Route::get('lst', '/lst')->name('systemCommunityCategoryLst')->option([
            '_alias' => '社区分类状态',
        ]);;
        Route::get('create/form', '/createForm')->name('systemCommunityCategoryCreateForm')->option([
            '_alias' => '社区分类添加表单',
            '_auth' => false,
            '_form' => 'systemCommunityCategoryCreate',
        ]);;
        Route::post('create', '/create')->name('systemCommunityCategoryCreate')->option([
            '_alias' => '社区分类添加',
        ]);;
        Route::get('update/:id/form', '/updateForm')->name('systemCommunityCategoryUpdateForm')->option([
            '_alias' => '社区分类编辑表单',
            '_auth' => false,
            '_form' => 'systemCommunityCategoryUpdate',
        ]);;
        Route::post('update/:id', '/update')->name('systemCommunityCategoryUpdate')->option([
            '_alias' => '社区分类编辑',
        ]);;
        Route::get('detail/:id', '/detail')->name('systemCommunityCategoryDetail')->option([
            '_alias' => '社区分类详情',
        ]);;
        Route::delete('delete/:id', '/delete')->name('systemCommunityCategoryDelete')->option([
            '_alias' => '社区分类删除',
        ]);;
        Route::post('status/:id', '/switchStatus')->name('systemCommunityCategoryStatus')->option([
            '_alias' => '社区分类修改状态',
        ]);;
        Route::get('option', '/getOptions')->option([
            '_alias' => '社区分类',
            '_auth'  => false,
        ]);;
    })->prefix('admin.community.CommunityCategory')->option([
        '_path' => '/community/category',
        '_auth' => true,
    ]);

    //社区话题
    Route::group('community/topic', function () {
        Route::get('lst', '/lst')->name('systemCommunityTopicLst')->option([
            '_alias' => '社区话题',
        ]);
        Route::get('create/form', '/createForm')->name('systemCommunityTopicCreateForm')->option([
            '_alias' => '社区话题添加表单',
            '_auth' => false,
            '_form' => 'systemCommunityTopicCreate',
        ]);
        Route::post('create', '/create')->name('systemCommunityTopicCreate')->option([
            '_alias' => '社区话题添加',
        ]);
        Route::get('update/:id/form', '/updateForm')->name('systemCommunityTopicUpdateForm')->option([
            '_alias' => '社区话题编辑表单',
            '_auth' => false,
            '_form' => 'systemCommunityTopicUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemCommunityTopicUpdate')->option([
            '_alias' => '社区话题编辑',
        ]);
        Route::get('detail/:id', '/detail')->name('systemCommunityTopicDetail')->option([
            '_alias' => '社区话题详情 ',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemCommunityTopicDelete')->option([
            '_alias' => '社区话题删除',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemCommunityTopicStatus')->option([
            '_alias' => '社区话题修改状态',
        ]);
        Route::post('hot/:id', '/switchHot')->name('systemCommunityTopicHot')->option([
            '_alias' => '社区话题推荐',
        ]);
        Route::get('option', '/getOptions')->option([
            '_alias' => '社区话题',
            '_auth'  => false,
        ]);
    })->prefix('admin.community.CommunityTopic')->option([
        '_path' => '/community/topic',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/community/topic',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/community/topic',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //社区评论
    Route::group('community/reply', function () {
        Route::get('lst', '/lst')->name('systemCommunityReplyLst')->option([
            '_alias' => '社区评论列表',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemCommunityReplyDelete')->option([
            '_alias' => '社区评论删除',
        ]);
        Route::get('status/:id/form', '/statusForm')->name('systemCommunityReplyStatusForm')->option([
            '_alias' => '社区评论审核表单',
            '_auth' => false,
            '_form' => 'systemCommunityReplyStatus',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemCommunityReplyStatus')->option([
            '_alias' => '社区评论审核',
        ]);
    })->prefix('admin.community.CommunityReply')->option([
        '_path' => '/community/reply',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
