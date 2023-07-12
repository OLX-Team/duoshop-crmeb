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

    //身份规则
    Route::group('system/role', function () {
        Route::get('lst', '/getList')->name('systemRoleGetList')->option([
            '_alias' => '身份列表',
            ]);
        Route::post('create', '/create')->name('systemRoleCreate')->option([
            '_alias' => '身份添加',
            ]);
        Route::get('create/form', '/createForm')->name('systemRoleCreateForm')->option([
            '_alias' => '身份添加表单',
            '_auth' => false,
            '_form' => 'systemRoleCreate',
        ]);
        Route::post('update/:id', '/update')->name('systemRoleUpdate')->option([
            '_alias' => '身份编辑',
            ]);
        Route::get('update/form/:id', '/updateForm')->name('systemRoleUpdateForm')->option([
            '_alias' => '身份编辑表单',
            '_auth' => false,
            '_form' => 'systemRoleUpdate',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemRoleStatus')->option([
            '_alias' => '身份修改状态',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemRoleDelete')->option([
            '_alias' => '身份删除',
            ]);
    })->prefix('admin.system.auth.Role')->option([
        '_path' => '/setting/systemRole',
        '_auth' => true,
    ]);

    //Admin管理
    Route::group('system/admin', function () {
        Route::get('lst', '.Admin/getList')->name('systemAdminLst')->option([
            '_alias' => '管理员列表',
            ]);
        Route::post('status/:id', '.Admin/switchStatus')->name('systemAdminStatus')->option([
            '_alias' => '管理员修改状态',
            ]);
        Route::get('create/form', '.Admin/createForm')->name('systemAdminCreateForm')->option([
            '_alias' => '管理员添加表单',
            '_auth' => false,
            '_form' => 'systemAdminCreate',
        ]);
        Route::post('create', '.Admin/create')->name('systemAdminCreate')->option([
            '_alias' => '管理员添加',
            ]);

        Route::get('update/form/:id', '.Admin/updateForm')->name('systemAdminUpdateForm')->option([
            '_alias' => '管理员编辑表单',
            '_auth' => false,
            '_form' => 'systemAdminUpdate',
        ]);
        Route::post('update/:id', '.Admin/update')->name('systemAdminUpdate')->option([
            '_alias' => '管理员编辑',
        ]);

        Route::get('password/form/:id', '.Admin/passwordForm')->name('systemAdminPasswordForm')->option([
            '_alias' => '管理员修改密码表单',
            '_auth' => false,
            '_form' => 'systemAdminPassword',
        ]);
        Route::post('password/:id', '.Admin/password')->name('systemAdminPassword')->option([
            '_alias' => '管理员修改密码',
            ]);


        Route::delete('delete/:id', '.Admin/delete')->name('systemAdminDelete')->option([
            '_alias' => '管理员删除',
            ]);

        Route::get('log', '.AdminLog/lst')->name('systemAdminLog')->option([
            '_alias' => '操作日志',
            '_path'  => '/setting/systemLog',
        ]);

    })->prefix('admin.system.admin')->option([
        '_path' => '/setting/systemAdmin',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/setting/systemAdmin',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/setting/systemAdmin',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    Route::group('system/admin', function () {
        Route::get('edit/form', '.Admin/editForm')->name('systemAdminEditForm')->option([
            '_alias' => '修改信息表单',
            '_auth' => false,
            '_form' => 'systemAdminEdit',
        ]);
        Route::post('edit', '.Admin/edit')->name('systemAdminEdit')->option([
            '_alias' => '修改信息',
        ]);
        Route::get('edit/password/form', '.Admin/editPasswordForm')->name('systemAdminEditPasswordForm')->option([
            '_alias' => '修改密码表单',
            '_auth' => false,
            '_form' => 'systemAdminEditPassword',
        ]);
        Route::post('edit/password', '.Admin/editPassword')->name('systemAdminEditPassword')->option([
            '_alias' => '修改密码',
        ]);
    })->prefix('admin.system.admin')->option([
        '_path' => 'self',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
