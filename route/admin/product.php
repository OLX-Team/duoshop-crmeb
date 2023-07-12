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

    //参数模板
    Route::group('store/params', function () {
        Route::get('temp/lst', '/lst')->name('systemStoreParameterTemplateLst')->option([
            '_alias' => '平台参数列表',
        ])->append(['is_mer' => 0]);
        Route::get('temp/merlst', '/lst')->name('systemStoreParameterTemplateMerLst')->option([
            '_alias' => '商户参数模板',
            '_path' => '/product/merSpecs',
        ])->append(['is_mer' => 1]);

        Route::get('temp/detail/:id', '/detail')->name('systemStoreParameterTemplateDetail')->option([
            '_alias' => '详情',
        ]);
        Route::delete('temp/delete/:id', '/delete')->name('systemStoreParameterTemplateDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('temp/create', '/create')->name('systemStoreParameterTemplateCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('temp/update/:id', '/update')->name('systemStoreParameterTemplateUpdate')->option([
            '_alias' => '编辑',
        ]);
    })->prefix('admin.parameter.ParameterTemplate')->option([
        '_path' => '/product/specs',
        '_auth' => true,
    ]);

    //商品分类
    Route::group('store/category', function () {
        Route::get('create/form', '/createForm')->name('systemStoreCategoryCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemStoreCategoryCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('systemStoreCategoryUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemStoreCategoryUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemStoreCategoryUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::get('lst', '/lst')->name('systemStoreCategoryLst')->option([
            '_alias' => '列表',
            ]);
        Route::get('detail/:id', '/detail')->name('systemStoreCategoryDtailt')->option([
            '_alias' => '详情',
            ]);
        Route::post('create', '/create')->name('systemStoreCategoryCreate')->option([
            '_alias' => '添加',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemStoreCategoryDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemStoreCategorySwitchStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('is_hot/:id', '/switchIsHot')->name('systemStoreCategorySwitchIsHot')->option([
            '_alias' => '修改推荐',
        ]);
        Route::get('list', '/getList')->option([
            '_alias' => '筛选',
            '_auth'  => false,
        ]);
    })->prefix('admin.store.StoreCategory')->option([
        '_path' => '/product/classify',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/product/classify',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/product/classify',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //品牌分类
    Route::group('store/brand/category', function () {
        Route::get('create/form', '/createForm')->name('systemStoreBrandCategoryCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemStoreBrandCategoryCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('systemStoreBrandCategoryUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemStoreBrandCategoryUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemStoreBrandCategoryUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::get('lst', '/lst')->name('systemStoreBrandCategoryLst')->option([
            '_alias' => '列表',
            ]);
        Route::get('detail/:id', '/detail')->name('systemStoreBrandCategoryDtailt')->option([
            '_alias' => '详情',
            ]);
        Route::post('create', '/create')->name('systemStoreBrandCategoryCreate')->option([
            '_alias' => '添加',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemStoreBrandCategoryDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('status/:id', '/switchStatus')->name('systemStoreBrandCategorySwitchStatus')->option([
            '_alias' => '修改状态',
            ]);
    })->prefix('admin.store.StoreBrandCategory')->option([
        '_path' => '/product/band/brandClassify',
        '_auth' => true,
    ]);

    //品牌
    Route::group('store/brand', function () {
        Route::get('create/form', '/createForm')->name('systemStoreBrandCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemStoreBrandCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('systemStoreBrandUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemStoreBrandUpdate',
        ]);
        Route::get('lst', '/lst')->name('systemStoreBrandLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemStoreBrandSwithStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('create', '/create')->name('systemStoreBrandCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('update/:id', '/update')->name('systemStoreBrandUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemStoreBrandDelete')->option([
            '_alias' => '删除',
        ]);
    })->prefix('admin.store.StoreBrand')->option([
        '_path' => '/product/band/brandList',
        '_auth' => true,
    ]);

    //商品
    Route::group('store/product', function () {
        Route::get('mer_select', '/lists')->option([
            '_alias' => '列表',
            '_auth'  => false,
        ]);
        Route::get('lst_filter', '/getStatusFilter')->name('systemStoreProductLstFilter')->option([
            '_alias' => '统计',
        ]);
        Route::get('lst', '/lst')->name('systemStoreProductLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('list', '/lst')->option([
            '_alias' => '',
            '_auth'  => false,
        ]);
        Route::get('detail/:id', '/detail')->name('systemStoreProductDetail')->option([
            '_alias' => '详情',
        ]);
        Route::post('update/:id', '/update')->name('systemStoreProductUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('status', '/switchStatus')->name('systemStoreProductSwitchStatus')->option([
            '_alias' => '上下架',
        ]);
        Route::post('batch_status', '/batchShow')->name('systemStoreProductSwitchBatchStatus')->option([
            '_alias' => '批量上下架',
        ]);
        Route::post('batch_labels', '/batchLabels')->name('systemStoreProductSwitchBatchLabels')->option([
            '_alias' => '批量设置标签',
        ]);
        Route::post('batch_hot', '/batchHot')->name('systemStoreProductSwitchBatchHot')->option([
            '_alias' => '批量设置推荐',
        ]);
        Route::post('check', '/checkProduct')->name('systemStoreProductCheck')->option([
            '_alias' => '分销状态变更商品检测',
        ]);
        Route::post('change/:id', '/changeUsed')->name('systemStoreProductChangeUsed')->option([
            '_alias' => '显示/隐藏',
        ]);
        Route::get('ficti/form/:id', '/addFictiForm')->name('systemStoreProductAddFictiForm')->option([
            '_alias' => '虚拟销量表单',
            '_auth' => false,
            '_form' => 'systemStoreProductAddFicti',
        ]);
        Route::post('ficti/:id', '/addFicti')->name('systemStoreProductAddFicti')->option([
            '_alias' => '虚拟销量',
            ]);
        Route::post('labels/:id', '/setLabels')->name('systemStoreProductLabels')->option([
            '_alias' => '设置标签',
            ]);
    })->prefix('admin.store.StoreProduct')->option([
        '_path' => '/product/examine',
        '_auth' => true,
    ]);

    //商品评价管理
    Route::group('store/reply', function () {
        Route::get('lst', '/lst')->name('systemProductReplyLst')->option([
            '_alias' => '列表',
            ]);
        Route::get('create/form/:id?', '/virtualForm')->name('systemProductReplyCreateForm')->option([
            '_alias' => '添加虚拟评论表单',
            '_auth' => false,
            '_form' => 'systemProductReplyCreate',
        ]);
        Route::post('create', '/virtualReply')->name('systemProductReplyCreate')->option([
            '_alias' => '添加虚拟评论',
            ]);
        Route::post('sort/:id', '/sort')->name('systemProductReplySort')->option([
            '_alias' => '排序',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemProductReplyDelete')->option([
            '_alias' => '删除',
            ]);
    })->prefix('admin.store.StoreProductReply')->option([
        '_path' => '/product/comment',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/product/comment',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/product/comment',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);


    //保障服务
    Route::group('guarantee', function () {
        Route::get('lst', '/lst')->name('systemGuaranteeLst')->option([
            '_alias' => '列表',
            ]);
        Route::get('create/form', '/createForm')->name('systemGuaranteeCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemGuaranteeCreate',
        ]);
        Route::post('create', '/create')->name('systemGuaranteeCreate')->option([
            '_alias' => '添加',
            ]);
        Route::get('update/:id/form', '/updateForm')->name('systemGuaranteeUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemGuaranteeUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemGuaranteeUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::get('detail/:id', '/detail')->name('systemGuaranteeDetail')->option([
            '_alias' => '详情',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemGuaranteeDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('sort/:id', '/sort')->name('systemGuaranteeSort')->option([
            '_alias' => '排序',
        ]);
        Route::post('status/:id', '/switchStatus')->name('systemGuaranteeStatus')->option([
            '_alias' => '修改状态',
            ]);
    })->prefix('admin.store.Guarantee')->option([
        '_path' => '/product/guarantee',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/product/guarantee',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/product/guarantee',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //商品标签
    Route::group('product/label', function () {
        Route::get('lst', '/lst')->name('systemStoreProductLabelLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('create/form', '/createForm')->name('systemStoreProductLabelCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemStoreProductLabelCreate',
        ]);
        Route::post('create', '/create')->name('systemStoreProductLabelCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('update/:id/form', '/updateForm')->name('systemStoreProductLabelUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemStoreProductLabelUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemStoreProductLabelUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::get('detail/:id', '/detail')->name('systemStoreProductLabelDetail')->option([
            '_alias' => '详情',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemStoreProductLabelDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('status/:id', '/switchWithStatus')->name('systemStoreProductLabelStatus')->option([
            '_alias' => '修改状态',
            ]);
        Route::get('option', '/getOptions')->option([
            '_alias' => '筛选',
            '_auth'  => false,
        ]);
    })->prefix('admin.store.ProductLabel')->option([
        '_path' => '/product/label',
        '_auth' => true,
    ]);

    Route::group('discounts/', function () {
        Route::get('lst', '/lst')->name('systemStoreDiscountsLst')->option([
            '_alias' => '优惠套餐列表',
            ]);
        Route::get('detail/:id', '/detail')->name('systemStoreDiscountsDetail')->option([
            '_alias' => '优惠套餐详情',
            ]);
        Route::post('status/:id', '/switchStatus')->name('systemStoreDiscountsStatus')->option([
            '_alias' => '优惠套餐修改状态',
            ]);
    })->prefix('admin.store.Discounts')->option([
        '_path' => '/marketing/discounts/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/marketing/discounts/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/marketing/discounts/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    Route::group('price_rule/', function () {
        Route::get('lst', '/lst')->name('systemPriceRuleLst')->option([
            '_alias' => '价格说明列表',
            ]);
        Route::post('create', '/create')->name('systemPriceRuleCreate')->option([
            '_alias' => '添加价格说明',
            ]);
        Route::post('update/:id', '/update')->name('systemPriceRuleUpdate')->option([
            '_alias' => '修改价格说明',
            ]);
        Route::post('status/:id', '/switchStatus')->name('systemPriceRuleStatus')->option([
            '_alias' => '价格说明修改状态',
            ]);
        Route::delete('del/:id', '/delete')->name('systemPriceRuleDelete')->option([
            '_alias' => '删除价格说明',
            ]);
    })->prefix('admin.store.PriceRule')->option([
        '_path' => '/product/priceDescription',
        '_auth' => true,
        '_append' => [
            [
                '_name' => 'uploadImage',
                '_path' => '/product/priceDescription',
                '_alias' => '上传图片',
                '_auth' => true,
            ],
            [
                '_name' => 'systemAttachmentLst',
                '_path' => '/product/priceDescription',
                '_alias' => '图片列表',
                '_auth' => true,
            ],
        ]
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
