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

    //分账商户
    Route::group('system/applyments', function () {
        Route::get('lst', '/lst')->name('systemMerchantApplymentsLst')->option([
            '_alias' => '分账商户申请列表',
        ]);
        Route::get('detail/:id', '/detail')->name('systemMerchantApplymentsDetail')->option([
            '_alias' => '分账商户申请详情',
        ]);
        Route::post('status/:id', '/switchWithStatus')->name('systemMerchantApplymentsStatus')->option([
            '_alias' => '分账商户申请审核',
        ]);
        Route::get('merchant/:id', '/getMerchant')->name('systemMerchantApplymentsGet')->option([
            '_alias' => '分账商户审核查询',
            '_auth'  => false,
        ]);
        Route::get('mark/:id/form', '/markForm')->name('systemMerchantApplymentsMarrk')->option([
            '_alias' => '分账商户申请备注表单',
            '_form'  => 'systemMerchantApplymentsMarrkSave',
            '_auth' => false,
        ]);
        Route::post('mark/:id', '/mark')->name('systemMerchantApplymentsMarrkSave')->option([
            '_alias' => '分账商户申请备注',
        ]);
    })->prefix('admin.system.merchant.MerchantApplyments')->option([
        '_path' => '/merchant/applyments',
        '_auth' => true,
    ]);

    //分账单
    Route::group('profitsharing', function () {
        Route::get('lst', '/getList')->name('systemOrderProfitsharingLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('again/:id', '/again')->name('systemOrderProfitsharingAgain')->option([
            '_alias' => '重新分账',
        ]);
        Route::get('export', '/export')->name('systemOrderProfitsharingExport')->option([
            '_alias' => '导出'
        ]);
    })->prefix('admin.order.OrderProfitsharing')->option([
        '_path' => '/merchant/applyList',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'systemStoreExcelLst',
                '_path'  =>'/merchant/applyList',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemStoreExcelDownload',
                '_path'  =>'/merchant/applyList',
                '_alias' => '导出下载',
                '_auth' => true,
            ],
        ]
    ]);

    Route::group('profitsharing', function () {
        Route::get('config', '/getProfitsharing')->name('systemOrderProfitsharingGetConfig')->option([
            '_alias' => '配置信息',
        ]);
        Route::post('config', '/setProfitsharing')->name('systemOrderProfitsharingSetConfig')->option([
            '_alias' => '配置保存',
        ]);
    })->prefix('admin.system.config.ConfigOthers')->option([
        '_path' => '/accounts/settings',
        '_auth' => true,
        '_doc'  => '分账',
    ]);

    //提现
    Route::group('user/extract', function () {
        Route::get('lst', 'UserExtract/lst')->name('systemUserExtractLst')->option([
            '_alias' => '申请列表',
        ]);
        Route::post('status/:id', 'UserExtract/switchStatus')->name('systemUserExtractSwitchStatus')->option([
            '_alias' => '审核',
        ]);
        Route::get('export', 'UserExtract/export')->name('systemUserExtractExport')->option([
            '_alias' => '导出',
        ]);
    })->prefix('admin.user.')->option([
        '_path' => '/accounts/extract',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'systemStoreExcelLst',
                '_path'  =>'/accounts/extract',
                '_alias' => '导出列表',
                '_auth'  => true,
                '_repeat' => true,
            ],
            [
                '_name'  =>'systemStoreExcelDownload',
                '_path'  =>'/accounts/extract',
                '_alias' => '导出下载',
                '_auth' => true,
                '_repeat' => true,
            ],
        ]
    ]);

    Route::group('receipt', function () {
        Route::get('lst', '/getList')->name('systemOrderReceiptList')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', '/detail')->name('systemOrderReceiptDetail')->option([
            '_alias' => '详情',
        ]);
    })->prefix('merchant.store.order.OrderReceipt')->option([
        '_path' => '/accounts/receipt',
        '_auth' => true,
    ]);

    //充值
    Route::group('user/recharge', function () {
        Route::get('list', 'UserRecharge/getList')->name('systemUserRechargeList')->option([
            '_alias' => '列表',
        ]);
        Route::get('total', 'UserRecharge/total')->name('systemUserRechargeTotal')->option([
            '_alias' => '统计',
        ]);
    })->prefix('admin.user.')->option([
        '_path' => '/accounts/bill',
        '_auth' => true,
        '_doc'  => '充值管理',
    ]);

    //余额变动记录
    Route::group('bill', function () {
        Route::get('list', 'UserBill/getList')->name('systemUserBillList')->option([
            '_alias' => '列表',
        ]);
        Route::get('type', 'UserBill/type')->option([
            '_alias' => '类型',
            '_auth'  => false,
        ]);
        Route::get('export', 'UserBill/export')->name('systemUserBillExport')->option([
            '_alias' => '导出',
        ]);
    })->prefix('admin.user.')->option([
        '_path' => '/accounts/capital',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'systemStoreExcelLst',
                '_path'  =>'/accounts/capital',
                '_alias' => '导出列表',
                '_auth' => true,
                '_repeat' => true,
            ],
            [
                '_name'  =>'systemStoreExcelDownload',
                '_path'  =>'/accounts/capital',
                '_alias' => '导出下载',
                '_auth' => true,
                '_repeat' => true,
            ],
        ]
    ]);

    //账单管理
    Route::group('financial_record', function () {
        Route::get('lst', '/getList')->name('systemFinancialRecordLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('title', '/getTitle')->name('systemFinancialRecordTitle')->option([
            '_alias' => '统计',
        ]);
        Route::get('detail/:type', '/detail')->name('systemFinancialRecordDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('detail_export/:type', '/exportDetail')->name('systemFinancialRecordDetailExport')->option([
            '_alias' => '导出',
        ]);
    })->prefix('admin.system.merchant.FinancialRecord')->option([
        '_path' => '/accounts/statement',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'systemStoreExcelLst',
                '_path'  =>'/accounts/statement',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemStoreExcelDownload',
                '_path'  =>'/accounts/statement',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],
        ]
    ]);

    //资金流水
    Route::group('financial_record', function () {
        Route::get('list', '/lst')->name('systemFinancialRecordList')->option([
            '_alias' => '列表',
        ]);
        Route::get('export', '/export')->name('systemFinancialRecordExport')->option([
            '_alias' => '导出',
        ]);

        Route::get('count', '/title')->name('systemFinancialCount')->option([
            '_alias' => '统计',
        ]);

    })->prefix('admin.system.merchant.FinancialRecord')->option([
        '_path' => '/accounts/capitalFlow',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'systemStoreExcelLst',
                '_path'  =>'/accounts/capitalFlow',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemStoreExcelDownload',
                '_path'  =>'/accounts/capitalFlow',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],
        ]
    ]);

    //财务
    Route::group('financial', function () {
        //申请转账
        Route::get('lst', 'Financial/lst')->name('systemFinancialList')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', 'Financial/detail')->name('systemFinancialDetail')->option([
            '_alias' => '详情',
        ]);
        Route::post('update/:id', 'Financial/update')->name('systemFinancialUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('status/:id', 'Financial/switchStatus')->name('systemFinancialSwitchStatus')->append(['type' => 0])->option([
            '_alias' => '修改状态',
        ]);
        Route::get('mark/:id/form', 'Financial/markForm')->name('systemFinancialMarkForm')->option([
            '_alias' => '备注表单',
            '_form'  => 'systemFinancialMark',
            '_auth' => false,
        ]);
        Route::post('mark/:id', 'Financial/mark')->name('systemFinancialMark')->option([
            '_alias' => '备注',
        ]);
        Route::get('title', 'Financial/title')->name('systemFinancialTitle')->option([
            '_alias' => '统计',
        ]);
        Route::get('export', 'Financial/export')->name('systemFinancialExport')->option([
            '_alias' => '导出',
        ]);
    })->prefix('admin.system.financial.')->option([
        '_path' => '/accounts/transferRecord',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'systemStoreExcelLst',
                '_path'  =>'/accounts/transferRecord',
                '_alias' => '导出列表',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemStoreExcelDownload',
                '_path'  =>'/accounts/transferRecord',
                '_alias' => '导出下载',
                '_auth'  => true,
            ],
        ]
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
