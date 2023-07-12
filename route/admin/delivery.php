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

    //同城配送
    Route::group('delivery', function () {
        //配置表单
        Route::get('/config/form', 'DeliveryStation/deliveryForm')->name('systemDeliveryConfigForm')->option([
            '_alias' => '配置表单',
            '_auth' => false,
            '_form' => 'saveDeliveryConfig',
        ]);
        //保存
        Route::post('/config/save', 'DeliveryStation/saveDeliveryConfig')->name('systemDeliveryConfigSave')->option([
            '_alias' => '编辑配置',
            '_path' => '/systemForm/delivery',
            ]);

        //门店列表
        Route::get('station/lst', 'DeliveryStation/lst')->name('systemDeliveryStationlst')->option([
            '_alias' => '门店列表',
            '_path' => '/delivery/station',
            ]);

        //详情
        Route::get('station/detail/:id', 'DeliveryStation/detail')->name('systemDeliveryStationDetail')->option([
            '_alias' => '门店详情',
            '_path' => '/delivery/station',
        ]);

        //
        Route::get('station/options', 'DeliveryStation/options')->name('systemStoreDeliveryOptions')->option([
            '_alias' => '门店筛选',
            '_auth'  => false,
        ]);

        //详情
        Route::get('recharge', 'DeliveryStation/getRecharge')->name('systemDeliveryStationGetRecharge')->option([
            '_alias' => '充值',
            '_auth'  => false,
        ]);

        //lst
        Route::get('order/lst', 'DeliveryOrder/lst')->name('systemDeliveryOrderLst')->option([
            '_alias' => '配送记录',
            '_path' => '/delivery/usage_record',
            ]);

        //详情
        Route::get('order/detail/:id', 'DeliveryOrder/detail')->name('systemDeliveryOrderDetail')->option([
            '_alias' => '配送详情',
            '_path' => '/delivery/usage_record',
            ]);


        //充值记录
        Route::get('station/payLst', 'DeliveryStation/payLst')->name('systemDeliveryStationPaayyLst')->option([
            '_alias' => '充值记录',
            '_path' => '/delivery/recharge_record',
            ]);

        //lst
        Route::get('title', 'DeliveryOrder/title')->name('systemDeliveryOrderTitle')->option([
            '_alias' => '统计',
            '_path' => '/delivery/recharge_record',
            ]);

        //详情
        Route::get('belence', 'DeliveryStation/getBalance')->name('systemDeliveryStationGetBalance')->option([
            '_alias' => '余额',
            '_path' => '/delivery/recharge_record',
            ]);

    })->prefix('admin.delivery.')->option([
        '_auth' => true,
    ]);

    //地址快快递公司
    Route::group('store/express', function () {
        Route::get('lst', '/lst')->name('systemExpressLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemExpressSwitchStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('systemExpressUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemExpressUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemExpressUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemExpressDelete')->option([
            '_alias' => '删除',
        ]);
        Route::get('sync', '/syncAll')->name('systemExpressSync')->option([
            '_alias' => '同步',
        ]);
        Route::get('/options','/options')->option([
            '_alias' => '列表',
            '_auth' => false,
        ]);
    })->prefix('admin.store.Express')->option([
        '_path' => '/freight/express',
        '_auth' => true,
    ]);

    //地址快快递公司
    Route::group('store/city', function () {
        Route::get('lst/:id', '/lst')->name('systemCityAreaLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('create/form/:id', '/createForm')->name('systemCityAreaCreateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemCityAreaCreate',
        ]);
        Route::post('create', '/create')->name('systemCityAreaCreate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('update/:id/form', '/updateForm')->name('systemCityAreaUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemExpressUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemCityAreaUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemCityAreaDelete')->option([
            '_alias' => '删除',
        ]);
    })->prefix('admin.store.CityArea')->option([
        '_path' => '/freight/express',
        '_auth' => true,
    ]);


})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
