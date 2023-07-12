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

    Route::group(function () {
        Route::get('config/:key', 'Config/form')->name('configForm')->option([
            '_alias' => '获取配置信息',
            '_form'  => 'configSave',
            '_auth' => false,
        ]);

        Route::post('config/save/:key', 'ConfigValue/save')->name('configSave')->option([
            '_alias' => '编辑配置信息',
            '_auth' => true,
        ]);

    })->prefix('admin.system.config.')->option([
        '_init' => [ \crmeb\services\UpdateAuthInit::class,'config'],
    ]);

    //配置分类
    Route::group('config/classify', function () {
        Route::get('create/table', '/createTable')->name('configClassifyCreateForm')->option([
            '_alias' => '配置分类添加表单',
            '_auth' => false,
            '_form' => 'configClassifyCreate',
        ]);
        Route::post('create', '/create')->name('configClassifyCreate')->option([
            '_alias' => '配置分类添加',
        ]);
        Route::delete('delete/:id', '/delete')->name('configClassifyDelete')->option([
            '_alias' => '配置分类删除',
        ]);
        Route::post('update/:id', '/update')->name('configClassifyUpdate')->option([
            '_alias' => '配置分类编辑',
        ]);
        Route::get('update/table/:id', '/updateTable')->name('configClassifyUpdateForm')->option([
            '_alias' => '配置分类编辑表单',
            '_auth' => false,
            '_form' => 'configClassifyUpdate',
        ]);
        Route::post('status/:id', '/switchStatus')->name('configClassifySwitchStatus')->option([
            '_alias' => '配置分类修改状态',
        ]);
        Route::get('lst', '/lst')->name('configClassifyLst')->option([
            '_alias' => '配置分类列表',
        ]);
        Route::get('options', '/getOptions')->option([
            '_alias' => '配置分类筛选',
            '_auth'  => false,
        ]);

    })->prefix('admin.system.config.ConfigClassify')->option([
        '_path' => '/config/classify',
        '_auth' => true,
    ]);

    Route::group('config/setting', function () {
        Route::get('create/table', '/createTable')->name('configSettingCreateForm')->option([
            '_alias' => '配置添加表单',
            '_auth' => false,
            '_form' => 'configSettingCreate',
        ]);
        Route::post('create', '/create')->name('configSettingCreate')->option([
            '_alias' => '配置添加',
        ]);
        Route::post('update/:id', '/update')->name('configSettingUpdate')->option([
            '_alias' => '配置编辑',
        ]);
        Route::get('update/table/:id', '/updateTable')->name('configSettingUpdateForm')->option([
            '_alias' => '配置编辑表单',
            '_auth' => false,
            '_form' => 'configSettingUpdate',
        ]);
        Route::post('status/:id', '/switchStatus')->name('configSettingSwitchStatus')->option([
            '_alias' => '配置修改状态',
        ]);
        Route::get('lst', '/lst')->name('configSettingLst')->option([
            '_alias' => '配置列表',
        ]);
        Route::delete('delete/:id', '/delete')->name('configSettingDelete')->option([
            '_alias' => '配置删除',
        ]);
    })->prefix('admin.system.config.Config')->option([
        '_path' => '/config/setting',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
