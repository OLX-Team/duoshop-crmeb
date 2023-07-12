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

    Route::group('group', function () {
        Route::get('lst', '/lst')->name('groupLst')->option([
            '_alias' => '组合数据配置列表',
        ]);
        Route::post('create', '/create')->name('groupCreate')->option([
            '_alias' => '组合数据配置添加',
        ]);
        Route::post('update/:id', '/update')->name('groupUpdate')->option([
            '_alias' => '组合数据配置编辑',
        ]);
        Route::get('create/table', '/createTable')->name('groupCreateForm')->option([
            '_alias' => '组合数据配置添加表单',
            '_auth' => false,
            '_form' => 'groupCreate',
        ]);
        Route::get('update/table/:id', '/updateTable')->name('groupUpdateForm')->option([
            '_alias' => '组合数据配置编辑表单',
            '_auth' => false,
            '_form' => 'groupUpdate',
        ]);
    })->prefix('admin.system.groupData.Group')->option([
        '_path' => '/group/list',
        '_auth' => true,
    ]);

    Route::group('group', function () {
        Route::get('detail/:id', '/get')->name('groupDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('data/lst/:groupId', 'Data/lst')->name('groupDataLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('data/create/table/:groupId', 'Data/createTable')->name('groupDataCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'groupDataCreate',
        ]);
        Route::post('data/create/:groupId', 'Data/create')->name('groupDataCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('data/update/table/:groupId/:id', 'Data/updateTable')->name('groupDataUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'groupDataUpdate',
        ]);
        Route::post('data/update/:groupId/:id', 'Data/update')->name('groupDataUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('data/delete/:id', 'Data/delete')->name('groupDataDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('data/status/:id', 'Data/changeStatus')->name('groupDataChangeStatus')->option([
            '_alias' => '修改状态',
        ]);
    })->prefix('admin.system.groupData.Group')->option([
        '_path' => '/group/list',
        '_auth' => true,
        '_init'  => [ \crmeb\services\UpdateAuthInit::class,'groupData'],
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
