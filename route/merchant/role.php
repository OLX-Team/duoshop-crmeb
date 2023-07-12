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
use app\common\middleware\AllowOriginMiddleware;
use app\common\middleware\LogMiddleware;
use app\common\middleware\MerchantAuthMiddleware;
use app\common\middleware\MerchantTokenMiddleware;
use think\facade\Route;
use app\common\middleware\MerchantCheckBaseInfoMiddleware;

Route::group(function () {
    //身份规则
    Route::group('system/role', function () {
        Route::get('lst', '/getList')->name('merchantRoleGetList')->option([
            '_alias' => '列表',
            '_auth'  => false,
        ]);
        Route::post('create', '/create')->name('merchantRoleCreate')->option([
            '_alias' => '添加',
            '_auth'  => false,
        ]);
        Route::get('create/form', '/createForm')->name('merchantRoleCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantRoleCreate',
        ]);
        Route::post('update/:id', '/update')->name('merchantRoleUpdate')->option([
            '_alias' => '编辑',
            '_auth'  => false,
        ]);
        Route::get('update/form/:id', '/updateForm')->name('merchantRoleUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantRoleUpdate',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantRoleStatus')->option([
            '_alias' => '修改状态',
            '_auth'  => false,
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantRoleDelete')->option([
            '_alias' => '删除',
            '_auth'  => false,
        ]);
    })->prefix('merchant.system.auth.Role')->option([
        '_path' => '/setting/systemRole',
        '_auth' => true,
    ]);

    //Admin管理
    Route::group('system/admin', function () {
        Route::get('lst', '/getList')->name('merchantAdminLst')->option([
            '_alias' => '列表',
            ]);
        Route::post('status/:id', '/switchStatus')->name('merchantAdminStatus')->option([
            '_alias' => '修改状态',
            ]);
        Route::post('create', '/create')->name('merchantAdminCreate')->option([
            '_alias' => '添加',
            ]);
        Route::get('create/form', '/createForm')->name('merchantAdminCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantAdminCreate',
        ]);
        Route::post('update/:id', '/update')->name('merchantAdminUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('merchantAdminUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantAdminUpdate',
        ]);
        Route::post('password/:id', '/password')->name('merchantAdminPassword')->option([
            '_alias' => '修改密码',
            ]);
        Route::get('password/form/:id', '/passwordForm')->name('merchantAdminPasswordForm')->option([
            '_alias' => '修改密码表单',
            '_auth' => false,
            '_form' => 'merchantAdminPassword',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantAdminDelete')->option([
            '_alias' => '删除',
            ]);
    })->prefix('merchant.system.admin.MerchantAdmin')->option([
        '_path' => '/setting/systemAdmin',
        '_auth' => true,
    ]);

    Route::get('system/admin/log', 'admin.system.admin.AdminLog/lst')->name('merchantAdminLog')->option([
        '_alias' => '操作日志',
        '_path' => '/setting/systemLog',

    ]);

    Route::group('system/admin', function () {
        Route::get('edit/form', '/editForm')->name('merchantAdminEditForm')->option([
            '_alias' => '修改信息表单',
            '_auth' => false,
            '_form' => 'merchantAdminEdit',
        ]);
        Route::post('edit', '/edit')->name('merchantAdminEdit')->option([
            '_alias' => '修改信息',
        ]);
        Route::get('edit/password/form', '/editPasswordForm')->name('merchantAdminEditPasswordForm')->option([
            '_alias' => '修改密码表单',
            '_auth' => false,
            '_form' => 'merchantAdminEditPassword',
        ]);
        Route::post('edit/password', '/editPassword')->name('merchantAdminEditPassword')->option([
            '_alias' => '修改密码',
        ]);
    })->prefix('merchant.system.admin.MerchantAdmin')->option([
        '_path' => 'self',
        '_auth' => true,
    ]);


})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
