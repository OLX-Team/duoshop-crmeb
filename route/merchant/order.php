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
    //电子面单
    Route::group('expr',function(){

        Route::get('/temps','/getExportTemp')->name('merchantServeExportTemps')->option([
            '_alias' => '预览',
        ]);
        Route::get('/dump_lst','/dumpLst')->name('merchantServeExportDumpLst')->option([
            '_alias' => '默认模板',
        ]);

    })->prefix('admin.system.serve.Export')->option([
        '_path' => '/order/list',
        '_auth' => true,
    ]);

    //Order
    Route::group('store/order', function () {
        Route::get('excel', 'Order/excel')->name('merchantStoreOrderExcel')->option([
            '_alias' => '导出',
        ]);
        Route::get('printer/:id', 'Order/printer')->name('merchantStoreOrderPrinter')->option([
            '_alias' => '打印小票',
        ]);
        Route::get('chart', 'Order/chart')->name('merchantStoreOrderTitle')->option([
            '_alias' => '统计',
        ]);

        Route::get('filtter', 'Order/orderType')->name('merchantStoreOrderFiltter')->option([
            '_alias' => '类型',
            '_auth' => false,
        ]);
        Route::get('lst', 'Order/lst')->name('merchantStoreOrderLst')->option([
            '_alias' => '列表',
        ]);

        Route::get('express/:id', 'Order/express')->name('merchantStoreOrderExpress')->option([
            '_alias' => '快递查询',
        ]);

        Route::post('delivery/:id', 'Order/delivery')->name('merchantStoreOrderDelivery')->option([
            '_alias' => '发货',
        ]);
        Route::post('delivery_batch', 'Order/batchDelivery')->name('merchantStoreOrderBatchDelivery')->option([
            '_alias' => '批量发货',
        ]);

        Route::get('delivery_export', 'Order/deliveryExport')->name('merchantStoreOrderDeliveryExport')->option([
            '_alias' => '导出发货单',
        ]);
        Route::get('title', 'Order/title')->name('merchantStoreOrderStat')->option([
            '_alias' => '头部统计',
        ]);

        Route::get('update/:id/form', 'Order/updateForm')->name('merchantStoreOrderUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantStoreOrderUpdate',
        ]);
        Route::post('update/:id', 'Order/update')->name('merchantStoreOrderUpdate')->option([
            '_alias' => '编辑',
        ]);

        Route::get('detail/:id', 'Order/detail')->name('merchantStoreOrderDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('log/:id', 'Order/status')->name('merchantStoreOrderLog')->option([
            '_alias' => '操作记录',
        ]);
        Route::get('remark/:id/form', 'Order/remarkForm')->name('merchantStoreOrderRemarkForm')->option([
            '_alias' => '备注表单',
            '_auth' => false,
            '_form' => 'merchantStoreOrderRemark',
        ]);
        Route::post('remark/:id', 'Order/remark')->name('merchantStoreOrderRemark')->option([
            '_alias' => '备注',
        ]);
        Route::get('verify/:code', 'Order/verifyDetail')->name('merchantStoreOrderVerifyDetail')->option([
            '_alias' => '核销详情',
        ]);
        Route::post('verify/:id', 'Order/verify')->name('merchantStoreOrderVerify')->option([
            '_alias' => '核销',
        ]);
        Route::post('delete/:id', 'Order/delete')->name('merchantStoreOrderDelete')->option([
            '_alias' => '删除',
        ]);
         Route::get('children/:id', 'Order/childrenList')->name('merchantStoreOrderChildrenList')->option([
             '_alias' => '关联订单',
         ]);
    })->prefix('merchant.store.order.')->option([
        '_path' => '/order/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantStoreExcelLst',
                '_path'  =>'/order/list',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantStoreExcelDownload',
                '_path'  =>'/order/list',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],
        ]
    ]);

    //Order
    Route::group('store/order', function () {
        Route::get('takechart', 'Order/takeChart')->name('merchantStoreTakeOrderTitle')->option([
            '_alias' => '统计',
            '_auth' => false,
        ]);
        Route::get('take_title', 'Order/takeTitle')->name('merchantStoreOrderTakeTitle')->option([
            '_alias' => '统计',
        ]);
        Route::get('takelst', 'Order/takeLst')->name('merchantStoreTakeOrderLst')->option([
            '_alias' => '列表',
        ]);
    })->prefix('merchant.store.order.')->option([
        '_path' => '/order/cancellation',
        '_auth' => true,
    ]);


    //退款订单
    Route::group('store/refundorder', function () {
        Route::get('lst', '/lst')->name('merchantStoreRefundOrderLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreRefundOrderDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('status/:id/form', '/switchStatusForm')->name('merchantStoreRefundOrderSwitchStatusForm')->option([
            '_alias' => '审核表单',
            '_auth' => false,
            '_form' => 'merchantStoreRefundOrderSwitchStatus',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantStoreRefundOrderSwitchStatus')->option([
            '_alias' => '审核',
        ]);
        Route::post('refund/:id', '/refundPrice')->name('merchantStoreRefundOrderRefund')->option([
            '_alias' => '收到退回商品后确认退款',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreRefundDelete')->option([
            '_alias' => '删除',
        ]);
        Route::get('mark/:id/form', '/markForm')->name('merchantStoreRefundMarkForm')->option([
            '_alias' => '备注表单',
            '_auth' => false,
            '_form' => 'merchantStoreRefundMark',
        ]);
        Route::post('mark/:id', '/mark')->name('merchantStoreRefundMark')->option([
            '_alias' => '备注',
        ]);
        Route::get('log/:id', '/log')->name('merchantStoreRefundLog')->option([
            '_alias' => '操作记录',
        ]);
        Route::get('express/:id', '/express')->name('merchantStoreRefundExpress')->option([
            '_alias' => '快递查询',
        ]);
        Route::get('excel', '/createExcel')->name('merchantStoreRefundCreateExcel')->option([
            '_alias' => '导出',
        ]);
    })->prefix('merchant.store.order.RefundOrder')->option([
        '_path' => '/order/refund',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantStoreExcelLst',
                '_path'  =>'/order/refund',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantStoreExcelDownload',
                '_path'  =>'/order/refund',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],

        ]
    ]);

    // 导入
    Route::group('store/import', function () {
        Route::post('/:type', 'StoreImport/import')->name('merchantStoreOrderDeliveryImport')->option([
            '_alias' => '导入',
        ]);
        Route::get('lst', 'StoreImport/lst')->name('merchantStoreOrderDeliveryImportLst')->option([
            '_alias' => '导入记录',
        ]);
        Route::get('detail/:id', 'StoreImport/detail')->name('merchantStoreOrderDeliveryImportDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('excel/:id', 'StoreImport/export')->name('merchantStoreOrderDeliveryImportExcel')->option([
            '_alias' => '导出',
        ]);
    })->prefix('merchant.store.')->option([
        '_path' => '/order/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantStoreExcelLst',
                '_path'  =>'/order/list',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantStoreExcelDownload',
                '_path'  =>'/order/list',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],

        ]
    ]);


})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
