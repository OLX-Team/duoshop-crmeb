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

    //一号通
    Route::group('serve',function(){
        Route::get('meal','Serve/meal')->name('merchantServeMeal')->option([
            '_alias' => '套餐列表',
        ]);
        Route::get('code','Serve/getQrCode')->name('merchantServeCode')->option([
            '_alias' => '支付二维码',
        ]);
        Route::get('paylst','Serve/lst')->name('merchantServeLst')->option([
            '_alias' => '购买记录',
        ]);
        Route::get('detail/:id','Serve/detail')->name('merchantServeDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('info','Config/info')->name('merchantServeInfo')->option([
            '_alias' => '账号信息',
        ]);
    })->prefix('merchant.system.serve.')->option([
        '_path' => '/setting/sms/sms_config/index',
        '_auth' => true,
    ]);

    Route::group('serve',function(){
        Route::get('config','Config/getConfig')->name('merchantServeGetConfig')->option([
            '_alias' => '配置',
            '_auth' => false,
            '_form' => 'merchantServeSetConfig',
        ]);
        Route::post('config','Config/setConfig')->name('merchantServeSetConfig')->option([
            '_alias' => '保存配置',
        ]);
    })->prefix('merchant.system.serve.')->option([
        '_path' => '/setting/sms/dumpConfig',
        '_auth' => true,
    ]);

    Route::get('/dump_lst','admin.system.serve.Export/dumpLst')->name('merchantServeExportDumpLst')->option([
        '_alias' => '使用记录',
        '_path' => '/setting/sms/sms_config/index',
    ]);


})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
