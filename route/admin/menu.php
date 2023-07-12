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

    //权限管理
    Route::group('system/menu', function () {
        Route::get('lst', '/getList')->name('systemMenuGetLst')->option([
            '_alias' => '平台菜单/权限列表',
            ]);
        Route::get('create/form', '/createForm')->name('systemMenuCreateForm')->option([
            '_alias' => '平台菜单/权限添加表单',
            '_auth' => false,
            '_form' => 'systemMenuCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('systemMenuUpdateForm')->option([
            '_alias' => '平台菜单/权限编辑表单',
            '_auth' => false,
            '_form' => 'systemMenuUpdate',
        ]);
        Route::post('create', '/create')->name('systemMenuCreate')->option([
            '_alias' => '平台菜单/权限添加',
            ]);
        Route::post('update/:id', '/update')->name('systemMenuUpdate')->option([
            '_alias' => '平台菜单/权限编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemMenuDelete')->option([
            '_alias' => '平台菜单/权限删除',
            ]);
    })->prefix('admin.system.auth.Menu')->option([
        '_path' => '/setting/menu',
        '_auth' => true,
    ]);

    //商户权限管理
    Route::group('merchant/menu', function () {
        Route::get('lst', '/getList')->name('systemMerchantMenuGetLst')->append(['merchant' => 1])->option([
            '_alias' => '商户菜单/权限列表',
            ]);
        Route::get('create/form', '/createForm')->name('systemMerchantMenuCreateForm')->append(['merchant' => 1])->option([
            '_alias' => '商户菜单/权限添加表单',
            '_auth' => false,
            '_form' => 'systemMerchantMenuCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('systemMerchantMenuUpdateForm')->append(['merchant' => 1])->option([
            '_alias' => '商户菜单/权限编辑表单',
            '_auth' => false,
            '_form' => 'systemMerchantMenuUpdate',
        ]);
        Route::post('create', '/create')->name('systemMerchantMenuCreate')->append(['merchant' => 1])->option([
            '_alias' => '商户菜单/权限添加',
            ]);
        Route::post('update/:id', '/update')->name('systemMerchantMenuUpdate')->append(['merchant' => 1])->option([
            '_alias' => '商户菜单/权限编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemMerchantMenuDelete')->append(['merchant' => 1])->option([
            '_alias' => '商户菜单/权限删除',
            ]);
    })->prefix('admin.system.auth.Menu')->option([
        '_path' => '/merchant/system',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
