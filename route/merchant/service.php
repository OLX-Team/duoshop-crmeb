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
use app\common\middleware\MerchantCheckBaseInfoMiddleware;
use app\common\middleware\MerchantTokenMiddleware;
use think\facade\Route;

Route::group(function () {

    //客服
    Route::group('service', function () {
        Route::get('list', 'StoreService/lst')->name('merchantServiceLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('login/:id', 'StoreService/login')->name('merchantServiceLogin')->option([
            '_alias' => '登录',
            ]);
        Route::post('create', 'StoreService/create')->name('merchantServiceCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('create/form', 'StoreService/createForm')->name('merchantServiceCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantServiceCreate',
        ]);
        Route::post('update/:id', 'StoreService/update')->name('merchantServiceUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('update/form/:id', 'StoreService/updateForm')->name('merchantServiceUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantServiceUpdate',
        ]);
        Route::post('status/:id', 'StoreService/changeStatus')->name('merchantServiceSwitchStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::delete('delete/:id', 'StoreService/delete')->name('merchantServiceDelete')->option([
            '_alias' => '删除',
        ]);
        Route::get('/:id/user', 'StoreService/serviceUserList')->name('merchantServiceServiceUserList')->option([
            '_alias' => '客服的全部用户 ',
            ]);
        Route::get('/:id/:uid/lst', 'StoreService/getUserMsnByService')->name('merchantServiceServiceUserLogLst')->option([
            '_alias' => '用户与客服聊天记录',
            ]);
        Route::get('mer/:id/user', 'StoreService/merchantUserList')->name('merchantServiceServiceMerchantUserList')->option([
            '_alias' => '客服的聊天用户列表',
            ]);
        Route::get('/:id/lst', 'StoreService/getUserMsnByMerchant')->name('merchantServiceMerchantUserLogLst')->option([
            '_alias' => '用户与商户聊天记录',
            ]);
    })->prefix('merchant.store.service.')->option([
        '_path' => '/config/service',
        '_auth' => true,
    ]);
    //客服自动回复
    Route::group('service/reply', function () {
        Route::get('list', 'StoreServiceReply/lst')->name('merchantServiceReplyLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('create', 'StoreServiceReply/create')->name('merchantServiceReplyCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('update/:id', 'StoreServiceReply/update')->name('merchantServiceReplyUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('status/:id', 'StoreServiceReply/changeStatus')->name('merchantServiceReplyStatus')->option([
            '_alias' => '切换状态',
        ]);
        Route::delete('delete/:id', 'StoreServiceReply/delete')->name('merchantServiceReplyDelete')->option([
            '_alias' => '删除',
        ]);
    })->prefix('merchant.store.service.')->option([
        '_path' => '/systemForm/customer_keyword',
        '_auth' => true,
    ]);


})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
