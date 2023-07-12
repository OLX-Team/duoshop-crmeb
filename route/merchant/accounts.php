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

    Route::group('financial', function () {
        Route::post('refund/margin', 'Financial/refundMargin')->name('merchantFinancialRefundMargin')->option([
            '_alias' => '退保证金检测',
        ]);
        Route::post('refund/margin_apply', 'Financial/refundMarginApply')->name('merchantFinancialRefundMarginApply')->option([
            '_alias' => '退保证金申请',
        ]);
    })->prefix('merchant.system.financial.')->option([
        '_path' => 'merchant/margin',
        '_auth' => true,
    ]);

    Route::group('financial', function () {
        Route::get('account/form', '/accountForm')->name('merchantFinancialAccountForm')->option([
            '_alias' => '收款方式表单',
            '_auth' => false,
            '_form' => 'merchantFinancialAccountSave',
        ]);
        Route::post('account', '/accountSave')->name('merchantFinancialAccountSave')->option([
            '_alias' => '收款方式',
        ]);
    })->prefix('merchant.system.financial.Financial')->option([
        '_path' => '/accounts/payType',
        '_auth' => true,
    ]);

    //转账记录
    Route::group('financial', function () {

        Route::get('lst', 'Financial/lst')->name('merchantFinancialLst')->option([
            '_alias' => '转账记录',
        ]);
        Route::get('detail/:id', 'Financial/detail')->name('merchantFinancialDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('create/form', 'Financial/createForm')->name('merchantFinancialCreateForm')->option([
            '_alias' => '申请表单',
            '_auth' => false,
            '_form' => 'merchantFinancialCreateSave',
        ]);
        Route::post('create', 'Financial/createSave')->name('merchantFinancialCreateSave')->option([
            '_alias' => '申请',
        ]);
        Route::delete('delete/:id', 'Financial/delete')->name('merchantFinancialDelete')->option([
            '_alias' => '删除',
        ]);
        Route::get('mark/:id/form', 'Financial/markForm')->name('merchantFinancialMarkForm')->option([
            '_alias' => '备注表单',
            '_auth' => false,
            '_form' => 'merchantFinancialMark',
        ]);
        Route::post('mark/:id', 'Financial/mark')->name('merchantFinancialMark')->option([
            '_alias' => '备注',
        ]);
        Route::get('export', 'Financial/export')->name('merchantFinancialExport')->option([
            '_alias' => '导出',
        ]);
        Route::post('refund/margin', 'Financial/refundMargin')->name('merchantFinancialRefundMargin')->option([
            '_alias' => '列表',
        ]);
    })->prefix('merchant.system.financial.')->option([
        '_path' => '/accounts/transManagement',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantStoreExcelLst',
                '_path'  =>'/accounts/transManagement',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantStoreExcelDownload',
                '_path'  =>'/accounts/transManagement',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],

        ]
    ]);


    //资金流水
    Route::group('financial_record', function () {
        //资金流水
        Route::get('list', '/lst')->name('merchantFinancialRecordList')->option([
            '_alias' => '列表',
        ]);
        Route::get('export', '/export')->name('merchantFinancialRecordExport')->option([
            '_alias' => '导出',
        ]);
        Route::get('count', '/title')->name('merchantFinancialCount')->option([
            '_alias' => '统计',
        ]);
    })->prefix('admin.system.merchant.FinancialRecord')->option([
        '_auth' => true,
        '_path'  => '/accounts/capitalFlow',
        '_append'=> [
            [
                '_name'  =>'merchantStoreExcelLst',
                '_path'  =>'/accounts/capitalFlow',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantStoreExcelDownload',
                '_path'  =>'/accounts/capitalFlow',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],

        ]
    ]);

    //账单管理
    Route::group('financial_record', function () {
        //账单管理
        Route::get('lst', '/getList')->name('merchantFinanciaRecordlLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('title', '/getTitle')->name('merchantFinancialTitle')->option([
            '_alias' => '统计',
        ]);
        Route::get('detail/:type', '/detail')->name('merchantFinancialRecordDetail')->option([
           '_alias' => '详情',
        ]);
        Route::get('detail_export/:type', '/exportDetail')->name('merchantFinancialRecordDetailExport')->option([
            '_alias' => '导出',
        ]);
    })->prefix('admin.system.merchant.FinancialRecord')->option([
        '_auth' => true,
        '_path'  => '/accounts/statement',
        '_append'=> [
            [
                '_name'  =>'merchantStoreExcelLst',
                '_path'  =>'/accounts/statement',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantStoreExcelDownload',
                '_path'  =>'/accounts/statement',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],

        ]
    ]);

    //发票
    Route::group('store/receipt', function () {
        Route::get('lst', '/lst')->name('merchantOrderReceiptLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantOrderReceiptDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('set_recipt', '/setRecipt')->name('merchantOrderReceiptSetRecipt')->option([
            '_alias' => '开发票',
        ]);
        Route::post('save_recipt', '/saveRecipt')->name('merchantOrderReceiptSave')->option([
            '_alias' => '保存发票',
        ]);
        Route::get('mark/:id/form', '/markForm')->name('merchantOrderReceiptMarkForm')->option([
            '_alias' => '备注表单',
            '_auth' => false,
            '_form' => 'merchantOrderReceiptMark',
        ]);
        Route::post('mark/:id', '/mark')->name('merchantOrderReceiptMark')->option([
            '_alias' => '备注',
        ]);
        Route::post('update/:id', '/update')->name('merchantOrderReceiptUpdate')->option([
            '_alias' => '编辑',
        ]);
    })->prefix('merchant.store.order.OrderReceipt')->option([
        '_path' => '/order/invoice',
        '_auth' => true,
    ]);


    //分账单
    Route::group('profitsharing', function () {
        Route::get('lst', '/getList')->name('merchantOrderProfitsharingLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('export', '/export')->name('merchantOrderProfitsharingExport')->option([
            '_alias' => '导出',
        ]);
    })->prefix('admin.order.OrderProfitsharing')->option([
        '_path' => '/systemForm/applyList',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantStoreExcelLst',
                '_path'  =>'/systemForm/applyList',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantStoreExcelDownload',
                '_path'  =>'/systemForm/applyList',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],

        ]
    ]);

    //申请分账商户
    Route::group('applyments',function(){
        Route::post('create','/create')->name('merchantApplymentsCreate')->option([
            '_alias' => '申请',
        ]);
        Route::get('detail','/detail')->name('merchantApplymentsDetail')->option([
            '_alias' => '详情',
        ]);
        Route::post('update/:id','/update')->name('merchantApplymentsUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('upload/:field','/uploadImage')->name('merchantApplymentsUpload')->option([
            '_alias' => '上传图片',
        ]);
        Route::get('check','/check')->name('merchantApplymentsCheck')->option([
            '_alias' => '查询审核结果',
            '_auth' => false,
        ]);
    })->prefix('merchant.system.MerchantApplyments')->option([
        '_path' => '/systemForm/applyments',
        '_auth' => true,
    ]);





})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
