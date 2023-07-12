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

    Route::group('points/cate', function () {
        Route::get('lst', '/lst')->name('pointsCateLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', '/detail')->name('pointsCateDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('select', '/select')->option([
            '_alias' => '筛选',
        ]);
        Route::post('create', '/create')->name('pointsCateCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('update/:id', '/update')->name('pointsCateUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('create/form', '/createForm')->name('pointsCateCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'pointsCateCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('pointsCateUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'pointsCateUpdate',
        ]);
        Route::post('status/:id', '/switchStatus')->name('pointsCateStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::delete('delete/:id', '/delete')->name('pointsCateStatus')->option([
            '_alias' => '修改状态',
        ]);
    })->prefix('admin.points.Category')->option([
        '_path' => '/marketing/integral/classify',
        '_auth' => true,
    ]);

    Route::group('points/product', function () {
        Route::get('lst', '/lst')->name('pointsProductLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('get_attr_value/:id', '/isFormatAttr')->name('pointsCateFormatAttr')->option([
            '_alias' => '获取规格',
        ]);
        Route::get('detail/:id', '/detail')->name('pointsProductDetail')->option([
            '_alias' => '编辑',
        ]);
        Route::post('create', '/create')->name('pointsProductCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('update/:id', '/update')->name('pointsProductUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('status/:id', '/switchStatus')->name('pointsProductStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::delete('delete/:id', '/delete')->name('pointsProductStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('preview', '/preview')->name('pointsProductPreview')->option([
            '_alias' => '预览',
        ]);
    })->prefix('admin.points.Product')->option([
        '_path' => '/marketing/integral/proList',
        '_auth' => true,
    ]);
    Route::group('points/order', function () {
        Route::get('lst', '/lst')->name('pointsOrderLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', '/detail')->name('pointsOrderDetail')->option([
            '_alias' => '编辑',
        ]);
        Route::post('delivery/:id', '/delivery')->name('pointsOrderDelivery')->option([
            '_alias' => '发货',
        ]);
        Route::post('delivery_batch', '/batchDelivery')->name('pointsOrderBatchDelivery')->option([
            '_alias' => '批量发货',
        ]);
        Route::get('express/:id', '/express')->name('pointsOrderExpress')->option([
            '_alias' => '快递查询',
        ]);
        Route::get('excel', '/Excel')->name('pointsOrderExcel')->option([
            '_alias' => '导出',
        ]);
        Route::get('mark/:id/form', '/remarkForm')->name('pointsOrderMarkForm')->option([
            '_alias' => '备注表单',
            '_auth' => false,
            '_form' => 'pointsOrderMark',
        ]);
        Route::post('mark/:id', '/remark')->name('pointsOrderMark')->option([
            '_alias' => '备注',
        ]);
        Route::get('status/:id', '/getStatus')->name('pointsOrderStatus')->option([
            '_alias' => '记录',
        ]);
        Route::delete('delete/:id', '/delete')->name('pointsOrderDelete')->option([
            '_alias' => '删除',
        ]);
    })->prefix('admin.points.Order')->option([
        '_path' => '/marketing/integral/orderList',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
