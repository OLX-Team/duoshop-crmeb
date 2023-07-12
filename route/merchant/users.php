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
    //搜索记录
    Route::get('user/search_log', 'admin.user.User/SearchLog')->name('merchantUserSearchLog')->option([
        '_alias' => '搜索记录',
        '_path' => '/user/searchRecord',
        '_auth' => true,
    ]);

    //商户用户列表
    Route::group('user', function () {
        Route::get('lst', '/getList')->name('merchantUserLst')->option([
            '_alias' => '列表',
        ]);
        //修改用户标签
        Route::get('change_label/form/:id', '/changeLabelForm')->name('merchantUserChangeLabelForm')->option([
            '_alias' => '修改标签表单',
            '_auth' => false,
            '_form' => 'merchantUserChangeLabel',
        ]);
        Route::post('change_label/:id', '/changeLabel')->name('merchantUserChangeLabel')->option([
            '_alias' => '修改标签',
        ]);
        Route::get('order/:uid', '/order')->name('merchantUserOrder')->option([
            '_alias' => '订单列表',
        ]);
        Route::get('coupon/:uid', '/coupon')->name('merchantUserCoupon')->option([
            '_alias' => '优惠券',
        ]);
    })->prefix('merchant.user.UserMerchant')->option([
        '_path' => '/user/list',
        '_auth' => true,
    ]);

    //用户自动标签
    Route::group('auto_label', function () {
        Route::get('lst', '/getList')->name('merchantLabelRuleLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('create', '/create')->name('merchantLabelRuleCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('update/:id', '/update')->name('merchantLabelRuleUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantLabelRuleDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('sync/:id', '/sync')->name('merchantLabelRuleSync')->option([
            '_alias' => '自动同步',
        ]);
    })->prefix('merchant.user.LabelRule')->option([
        '_path' => '/user/maticlabel',
        '_auth' => true,
    ]);

    //手动标签
    Route::group('user/label', function () {
        Route::get('lst', '/lst')->name('merchantUserLabelLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('user/label', '/create')->name('merchantUserLabelCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('form', '/createForm')->name('merchantUserLabelCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantUserLabelCreate',
        ]);
        Route::delete(':id', '/delete')->name('merchantUserLabelDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post(':id', '/update')->name('merchantUserLabelUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('form/:id', '/updateForm')->name('merchantUserLabelUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantUserLabelUpdate',
        ]);
    })->prefix('admin.user.UserLabel')->option([
        '_path' => '/user/label',
        '_auth' => true,
    ]);




})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
