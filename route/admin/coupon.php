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

    //优惠券
    Route::group('store/coupon', function () {
        Route::get('lst', '/lst')->name('systemStoreCouponLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('issue', '/issue')->name('systemCouponIssue')->option([
            '_alias' => '使用记录',
            '_path' => '/marketing/coupon/user',
        ]);
        Route::get('detail/:id', '/detail')->name('systemCouponDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('product/:id', '/product')->name('systemCouponProduct')->option([
            '_alias' => '商品列表',
        ]);
    })->prefix('admin.store.Coupon')->option([
        '_path' => '/marketing/coupon/list',
        '_auth' => true,
    ]);

    //优惠券
    Route::group('store/coupon', function () {

        Route::get('create/form', '/createForm')->name('systemCouponCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemCouponCreate',
        ]);
        Route::post('create', '/create')->name('systemCouponCreate')->option([
            '_alias' => '添加',
            ]);
        Route::get('update/:id/form', '/updateForm')->name('systemCouponUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemCouponUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemCouponUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemCouponDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('status/:id', '/switchStatus')->name('systemCouponStatus')->option([
            '_alias' => '修改状态',
            ]);
        Route::get('platformLst', '/platformLst')->name('systemCouponList')->option([
            '_alias' => '列表',
            ]);
        Route::get('show/:id', '/detail')->name('systemCouponShow')->option([
            '_alias' => '详情',
            ]);
        Route::get('sys/issue', '/platformIssue')->name('systemCouponIssue')->option([
            '_alias' => '使用记录',
            '_path' => '/marketing/Platform_coupon/couponRecord',
            ]);
        Route::get('sys/clone/:id/form', '/cloneForm')->name('systemCouponCloneForm')->option([
            '_alias' => '复制表单',
            '_auth' => false,
            '_form' => 'systemCouponCreate',
        ]);
        Route::get('show_lst/:id', '/showLst')->name('systemCouponShowLst')->option([
            '_alias' => '详情关联列表',
            ]);
        Route::post('send', '/send')->name('systemCouponSend')->option([
            '_alias' => '发送优惠券',
            '_path' => '/user/list',
            '_append' => [
                [
                    '_name'  =>'systemCouponList',
                    '_path'  =>'/user/list',
                    '_alias' => '优惠券列表',
                    '_auth'  => true,
                ],
            ]
        ]);
        Route::get('send/lst', '/sendLst')->name('systemCouponSendLst')->option([
            '_alias' => '发送记录',
            '_path' => '/marketing/Platform_coupon/couponSend',
        ]);
    })->prefix('admin.store.Coupon')->option([
        '_path' => '/marketing/Platform_coupon/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/marketing/Platform_coupon/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/marketing/Platform_coupon/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
