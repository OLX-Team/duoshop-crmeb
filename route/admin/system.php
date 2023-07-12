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

    Route::group('excel', function () {
        Route::get('lst', '/lst')->name('systemStoreExcelLst')->option([
            '_alias' => '列表'
        ]);
        Route::get('download/:id', '/download')->name('systemStoreExcelDownload')->option([
            '_alias' => '下载',
        ]);
        Route::get('type', '/type')->option([
            '_alias' => '类型',
            '_auth'  => false,
        ]);
    })->prefix('merchant.store.Excel')->option([
        '_path' => '/group/exportList',
        '_auth' => true,
    ]);


    Route::group('statistics', function () {
        Route::get('main', '/main')->name('systemStatisticsMain')->option([
            '_alias' => '主要数据',
            ]);
        Route::get('order', '/order')->name('systemStatisticsOrder')->option([
            '_alias' => '当日订单',
            ]);
        Route::get('order_num', '/orderNum')->name('systemStatisticsOrderNum')->option([
            '_alias' => '当日订单数',
            ]);
        Route::get('order_user', '/orderUser')->name('systemStatisticsOrderUser')->option([
            '_alias' => '当日支付人数',
            ]);
        Route::get('merchant_stock', '/merchantStock')->name('systemStatisticsMerchantStock')->option([
            '_alias' => '商户销量',
            ]);
        Route::get('merchant_rate', '/merchantRate')->name('systemStatisticsMerchantRate')->option([
            '_alias' => '商户访问量',
            ]);
        Route::get('merchant_visit', '/merchantVisit')->name('systemStatisticsMerchantVisit')->option([
            '_alias' => '商户销售额',
            ]);
        Route::get('user_data', '/userData')->name('systemStatisticsUserData')->option([
            '_alias' => '用户数据',
            ]);
    })->prefix('admin.Common')->option([
        '_path' => '/dashboard',
        '_auth' => true,
    ]);

    Route::get('statistics/user', 'merchant.Common/user')->name('systemStatisticsUser')->option([
        '_alias' => '成交用户',
        '_path' => '/dashboard',
        '_auth'  => true,
    ]);
    Route::get('statistics/user_rate', 'merchant.Common/userRate')->name('systemStatisticsUserRate')->option([
        '_alias' => '成交用户占比',
        '_path'  => '/dashboard',
        '_auth'  => true,
    ]);


    //安全维护
    Route::group('safety/database', function () {
        Route::get('lst', '/lst')->name('systemSafetyDatabaseLst')->option([
            '_alias' => '数据库列表',
        ]);
        Route::get('fileList', '/fileList')->name('systemSafetyDatabaseFileList')->option([
            '_alias' => '数据库备份列表',
        ]);
        Route::get('detail/:name', '/detail')->name('systemSafetyDatabaseDetail')->option([
            '_alias' => '数据库备份详情',
        ]);
        Route::post('backups', '/backups')->name('systemSafetyDatabaseBackups')->option([
            '_alias' => '备份',
        ]);
        Route::post('optimize', '/optimize')->name('systemSafetyDatabaseOptimize')->option([
            '_alias' => '数据库优化',
        ]);
        Route::post('repair', '/repair')->name('systemSafetyDatabaseRepair')->option([
            '_alias' => '数据库维护',
        ]);
        Route::get('download/:feilname', '/downloadFile')->name('systemSafetyDatabaseDownloadFile')->option([
            '_alias' => '数据库备份下载',
        ]);
        Route::delete('delete', '/deleteFile')->name('systemSafetyDatabaseDeleteFile')->option([
            '_alias' => '数据库备份删除',
        ]);
    })->prefix('admin.system.safety.Database')->option([
        '_path' => '/maintain/dataBackup',
        '_auth' => true,
    ]);

    Route::post('clear/cache', 'admin.system.Cache/clearCache')->name('systemClearCache')->option([
        '_alias' => '清除缓存',
        '_path' => '/maintain/cache',
        '_auth' => true,
    ]);

    Route::get('upload/config', 'admin.Common/uploadConfig')->name('systemUploadConfig')->option([
        '_alias' => '上传配置',
        '_form' => 'systemSaveUploadConfig',
        '_auth' => false,
    ]);

    Route::post('upload/config', 'admin.Common/saveUploadConfig')->name('systemSaveUploadConfig')->option([
        '_alias' => '上传配置保存',
        '_path' => '/systemForm/Basics/upload',
        '_auth' => true,
    ]);

    Route::get('upload/temp_key', 'admin.Common/temp_key')->name('systemSaveUploadTempKey')->option([
        '_alias' => '上传视屏KEY',
        '_auth' => false,
    ]);

    //协议
    Route::group('agreement', function () {

        Route::get('keylst', '/getKeyLst')->name('systemAgreeKeyLsy')->option([
            '_alias' => '协议列表',
        ]);

        Route::get(':key', '/getAgree')->name('systemAgreeDetail')->option([
            '_alias'=> '协议',
            '_auth' => false,
            '_form' => 'systemAgreeSave',
        ]);

        Route::post(':key', '/saveAgree')->name('systemAgreeSave')->option([
            '_alias' => '协议保存',
            '_init'  => [ \crmeb\services\UpdateAuthInit::class,'agreement'],
        ]);

    })->prefix('admin.system.Cache')->option([
        '_path' => '/setting/agreements',
        '_auth' => true,
    ]);

    Route::group('copyright', function () {

        Route::get('get', '/copyright')->name('systemCopyright')->option([
            '_alias' => '获取去版权信息',
        ]);
        Route::get('auth', '/authCopyright')->name('systemAuthCopyright')->option([
            '_alias' => '获取授权信息',
        ]);
        Route::post('save', '/svaeCopyright')->name('systemSaveCopyright')->option([
            '_alias' => '保存去版权信息',
        ]);
    })->prefix('admin.Common')->option([
        '_path' => '/maintain/auth',
        '_auth' => true,
    ]);

    Route::group(function () {

        Route::get('menus', 'admin.system.auth.Menu/menus');
        Route::get('system/city/lst', 'merchant.store.shipping.City/lst');
        //退出登陆
        Route::get('logout', 'admin.system.admin.Login/logout');
        //获取版本号
        Route::get('version', 'admin.Common/version');
        //授权
        Route::post('auth_apply', 'admin.Common/auth_apply');
        Route::get('check_auth', 'admin.Common/check_auth');
        Route::get('auth', 'admin.Common/auth');
        Route::get('pay/auth', 'admin.Common/payAuth');
        Route::get('config', 'admin.Common/config');
        Route::get('merchant/mer_auth', 'admin.system.merchant.MerchantType/mer_auth');
        Route::post('upload/video', 'merchant.Common/uploadVideo');
    })->option([
        '_auth' => false,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
