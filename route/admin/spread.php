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

    Route::group('user', function () {
        Route::get('promoter/lst', '/promoterList')->name('systemPromoterUserLst')->option([
            '_alias' => '分销员列表',
            ]);
        Route::get('promoter/count', '/promoterCount')->name('systemPromoterUserCount')->option([
            '_alias' => '分销员统计',
            ]);
        Route::get('/spread/:id/form', '/spreadLevelForm')->name('systemUserSpreadForm')->option([
            '_alias' => '修改分销员等级表单',
            '_auth' => false,
            '_form' => 'systemUserSpreadSave',
        ]);
        Route::post('/spread/:id/save', '/spreadLevelSave')->name('systemUserSpreadSave')->option([
            '_alias' => '修改分销员等级',
            ]);
    })->prefix('admin.user.User')->option([
        '_path' => '/promoter/user',
        '_auth' => true,
    ]);

    Route::group('user/brokerage', function () {
        Route::get('lst', '.UserBrokerage/getLst')->name('systemUserBrokerageLst')->option([
            '_alias' => '分销员等级列表',
            ]);
        Route::get('detail/:id', '.UserBrokerage/detail')->option([
            '_alias' => '分销员等级详情',
            '_auth'  => false,
        ]);
        Route::get('options', '.UserBrokerage/options')->option([
            '_alias' => '分销员等级筛选',
            '_auth'  => false,
        ]);
        Route::post('create', '.UserBrokerage/create')->name('systemUserBrokerageCreate')->option([
            '_alias' => '分销员等级添加',
            ]);
        Route::post('update/:id', '.UserBrokerage/update')->name('systemUserBrokerageUpdate')->option([
            '_alias' => '分销员等级编辑',
            ]);
        Route::delete('delete/:id', '.UserBrokerage/delete')->name('systemUserBrokerageDelete')->option([
            '_alias' => '分销员等级删除',
            ]);
    })->prefix('admin.user')->append(['type' => 0])->option([
        '_path' => '/promoter/membership_level',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/promoter/membership_level',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/promoter/membership_level',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //推广人
    Route::group('user/spread', function () {
        Route::get('lst/:uid', '/spreadList')->name('systemUserSpreadLst')->option([
            '_alias' => '推广人列表',
        ]);
        Route::get('order/:uid', '/spreadOrder')->name('systemUserSpreadOrder')->option([
            '_alias' => '推广人订单',
        ]);
        Route::post('clear/:uid', '/clearSpread')->name('systemUserSpreadClear')->option([
            '_alias' => '清除推广人',
        ]);
    })->prefix('admin.user.User')->option([
        '_path' => '/promoter/user',
        '_auth' => true,
    ]);

    //礼包
    Route::group('store/bag', function () {
        Route::get('mer_select', '/lists')->option([
            '_alias' => '商户列表',
            '_auth'  => false,
        ]);
        Route::get('list', '/lst')->option([
            '_alias' => '列表 ',
            '_auth'  => false,
        ]);
        Route::get('lst_filter', '/getBagStatusFilter')->name('systemStoreBagLstFilter')->option([
            '_alias' => '统计',
            ]);
        Route::get('lst', '/bagList')->name('systemStoreBagLst')->option([
            '_alias' => '列表',
            ]);
        Route::get('detail/:id', '/detail')->name('systemStoreBagDetail')->option([
            '_alias' => '详情',
            ]);
        Route::post('update/:id', '/update')->name('systemStoreBagUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::post('status', '/switchStatus')->name('systemStoreBagSwitchStatus')->option([
            '_alias' => '修改状态',
            ]);
        Route::post('change/:id', '/changeUsed')->name('systemStoreBagChangeUsed')->option([
            '_alias' => '显示/隐藏',
            ]);
    })->prefix('admin.store.StoreProduct')->option([
        '_path' => '/promoter/gift',
        '_auth' => true,
    ]);

    Route::group('config/others', function () {
        Route::post('update', 'ConfigOthers/update')->name('configOthersSettingUpdate')->option([
            '_alias' => '配置保存',
        ]);
    })->prefix('admin.system.config.')->option([
        '_path' => '/systemForm/Basics/distribution_tabs',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
