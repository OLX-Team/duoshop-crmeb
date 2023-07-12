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
use app\common\middleware\ServiceTokenMiddleware;
use think\facade\Route;

Route::group(config('admin.api_service_prefix'), function () {

    //验证码
    Route::get('captcha', 'service.Login/getCaptcha');
    Route::get('config', 'service.Common/config');
    //登录
    Route::post('login', 'service.Login/login');
    //扫码登录
    Route::post('login/scan', 'service.Login/scanLogin');
    //登录
    Route::post('login/scan/check', 'service.Login/checkScanLogin');

    Route::group(function () {
        //退出登录
        Route::post('logout', 'service.Login/logout');
        //商户信息
        Route::get('info', 'service.Common/info');
        //用户聊天列表
        Route::get('user/lst', 'service.Service/serviceUserList');
        //用户备注
        Route::post('user/mark/:uid', 'service.Service/mark');
        //聊天记录
        Route::get('history/:uid', 'service.Service/history');
        //用户信息
        Route::get('user', 'service.Common/user');
        //图片上传
        Route::post('upload/:field', 'service.Service/upload');
        //订单信息
        Route::get('order/:id', 'service.Service/getOrderInfo');

        Route::get('order_status/:id', 'service.Service/orderStatus');
        //退款单信息
        Route::get('refund/:id', 'service.Service/getRefundOder');
        //快递
        Route::get('order_express/:id', 'service.Service/orderExpress');
        //快递
        Route::get('refund_express/:id', 'service.Service/refundOrderExpress');
        //商品
        Route::get('product/:id', 'service.Service/product');

    })->middleware(ServiceTokenMiddleware::class, true);

})->middleware(AllowOriginMiddleware::class);
