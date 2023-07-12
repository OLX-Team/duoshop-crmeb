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

    //发送优惠券
    Route::get('store/coupon/product/:id', 'admin.store.Coupon/product')->name('merchantCouponProduct')->option([
        '_alias' => '优惠券可用商品',
        '_path' => '/user/list',
        '_auth' => true,
    ]);

    Route::get('store/coupon_send/lst', 'merchant.store.coupon.CouponSend/lst')->name('merchantCouponSendLst')->option([
            '_alias' => '发送优惠券记录',
            '_path' => '/marketing/coupon/send',
            '_auth' => true,
        ]);

    //优惠券
    Route::group('store/coupon', function () {
        Route::get('create/form', '/createForm')->name('merchantCouponCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantCouponCreate',
        ]);
        Route::get('clone/form/:id', '/cloneForm')->name('merchantCouponIssueCloneForm')->option([
            '_alias' => '复制表单',
            '_auth' => false,
            '_form' => 'merchantCouponCreate',
        ]);
        Route::post('create', '/create')->name('merchantCouponCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('status/:id', '/changeStatus')->name('merchantCouponIssueChangeStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::get('lst', '/lst')->name('merchantCouponLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('issue', '/issue')->name('merchantCouponIssue')->option([
            '_alias' => '使用记录',
            '_path' => '/marketing/coupon/user',
        ]);
        Route::get('select', '/select')->option([
            '_alias' => '筛选',
            '_auth'  => false,
        ]);

        Route::delete('delete/:id', '/delete')->name('merchantCouponDelete')->option([
            '_alias' => '删除',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantCouponDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('update/:id/form', '/updateForm')->name('systemCouponUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemCouponUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemCouponUpdate')->option([
            '_alias' => '编辑',
        ]);

    })->prefix('merchant.store.coupon.Coupon')->option([
        '_alias' => '配置保存',
        '_path' => '/marketing/coupon/list',
        '_auth'  => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/coupon/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/coupon/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    Route::post('store/coupon/send', 'merchant.store.coupon.Coupon/send')
        ->name('merchantCouponSendCoupon')->option([
            '_alias' => '发送优惠券',
            '_path' => '/user/list',
            '_auth'  => true,
        ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
