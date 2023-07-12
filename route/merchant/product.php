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

    //参数模板
    Route::group('store/params', function () {
        Route::get('temp/lst', '/lst')->name('merchantStoreParameterTemplateLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('temp/detail/:id', '/detail')->name('merchantStoreParameterTemplateDetail')->option([
            '_alias' => '详情',
        ]);
        Route::delete('temp/delete/:id', '/delete')->name('merchantStoreParameterTemplateDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('temp/create', '/create')->name('merchantStoreParameterTemplateCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('temp/update/:id', '/update')->name('merchantStoreParameterTemplateUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('temp/select', '/select')->option([
            '_alias' => '筛选列表',
            '_auth' => false,
        ]);
        Route::get('temp/show', '/show')->option([
            '_alias' => '参数',
            '_auth' => false,
        ]);
    })->prefix('admin.parameter.ParameterTemplate')->option([
        '_path' => '/product/specs',
        '_auth' => true,
    ]);

    //产品规则模板
    Route::group('store/attr/template', function () {
        Route::get('lst', '/lst')->name('merchantStoreAttrTemplateLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('list', '/getlist')->option([
            '_alias' => '筛选',
            '_auth'  => false,
        ]);
        Route::post('create', '/create')->name('merchantStoreAttrTemplateCreate')->option([
            '_alias' => '添加 ',
        ]);
        Route::delete(':id', '/delete')->name('merchantStoreAttrTemplateDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post(':id', '/update')->name('merchantStoreAttrTemplateUpdate')->option([
            '_alias' => '文件类型',
        ]);
    })->prefix('merchant.store.StoreAttrTemplate')->option([
        '_path' => '/product/attr',
        '_auth' => true,
    ]);

    //商品分类
    Route::group('store/category', function () {
        Route::get('create/form', '/createForm')->name('merchantStoreCategoryCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantStoreCategoryCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('merchantStoreCategoryUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantStoreCategoryUpdate',
        ]);
        Route::post('update/:id', '/update')->name('merchantStoreCategoryUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('lst', '/lst')->name('merchantStoreCategoryLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreCategoryDtailt')->option([
            '_alias' => '详情',
        ]);
        Route::post('create', '/create')->name('merchantStoreCategoryCreate')->option([
            '_alias' => '添加',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreCategoryDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantStoreCategorySwitchStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::get('list', '/getList')->option([
            '_alias' => '筛选',
            '_auth'  => false,
        ])->append(['type' => 1]);
        Route::get('select', '/getTreeList')->option([
            '_alias' => '树形',
            '_auth'  => false,
        ]);
        Route::get('brandlist', '/BrandList')->option([
            '_alias' => '品牌列表',
            '_auth'  => false,
        ]);
    })->prefix('admin.store.StoreCategory')->option([
        '_path' => '/product/classify',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/product/classify',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/product/classify',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //商品
    Route::group('store/product', function () {
        Route::get('config', '/config')->option([
            '_alias' => '配置',
            '_auth'  => false,
        ]);
        Route::get('lst_filter', '/getStatusFilter')->name('merchantStoreProductLstFilter')->option([
            '_alias' => '头部统计',
        ]);
        Route::get('lst', '/lst')->name('merchantStoreProductLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('list', '/lst')->option([
            '_alias' => '列表',
            '_auth'  => false,
        ]);
        Route::post('create', '/create')->name('merchantStoreProductCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreProductDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('temp_key', '/temp_key')->name('merchantStoreProductTempKey')->option([
            '_alias' => '上传视频配置',
        ]);
        Route::post('update/:id', '/update')->name('merchantStoreProductUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('free_trial/:id', '/freeTrial')->name('merchantStoreProductFreeTrial')->option([
            '_alias' => '免审编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreProductDelete')->option([
            '_alias' => '删除',
        ]);
        Route::delete('destory/:id', '/destory')->name('merchantStoreProductDestory')->option([
            '_alias' => '加入回收站',
        ]);
        Route::post('restore/:id', '/restore')->name('merchantStoreProductRestore')->option([
            '_alias' => '恢复',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantStoreProductSwitchStatus')->option([
            '_alias' => '上下架',
        ]);
        Route::post('batch_status', '/batchShow')->name('merchantStoreProductSwitchBatchStatus')->option([
            '_alias' => '批量上下架',
        ]);
        Route::post('batch_temp', '/batchTemplate')->name('merchantStoreProductSwitchBatchTemplate')->option([
            '_alias' => '批量设置运费模板',
        ]);
        Route::post('batch_labels', '/batchLabels')->name('merchantStoreProductSwitchBatchLabels')->option([
            '_alias' => '批量设置标签',
        ]);
        Route::post('batch_hot', '/batchHot')->name('merchantStoreProductSwitchBatchHot')->option([
            '_alias' => '批量设置推荐',
        ]);
        Route::post('batch_ext', '/batchExtension')->name('merchantStoreProductSwitchBatchExtension')->option([
            '_alias' => '批量设置推荐',
        ]);
        Route::post('batch_svip', '/batchSvipType')->name('merchantStoreProductSwitchBatchSvipType')->option([
            '_alias' => '批量设置会员价',
        ]);
        Route::post('sort/:id', '/updateSort')->name('merchantStoreProductUpdateSort')->option([
            '_alias' => '排序',
        ]);
        Route::post('preview', '/preview')->name('merchantStoreProductPreview')->option([
            '_alias' => '预览',
        ]);
        Route::post('labels/:id', '/setLabels')->name('merchantStoreProductLabels')->option([
            '_alias' => '标签',
        ]);
        Route::get('attr_value/:id', '/getAttrValue')->name('merchantStoreProductAttrValue')->option([
            '_alias' => '获取规格',
        ]);
        Route::post('get_attr_value/:id', '/isFormatAttr')->name('merchantStoreProductFormatAttr')->option([
            '_alias' => '获取规格',
        ]);

    })->prefix('merchant.store.product.Product')->option([
        '_path' => '/product/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/product/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/product/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],

        ]
    ]);


    //复制商品
    Route::group('store/productcopy', function () {
        Route::get('lst', '/lst')->name('merchantStoreProductCopyLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('get', '/get')->name('merchantStoreProductCopyGet')->option([
            '_alias' => '获取信息',
        ]);
        Route::get('count', '/count')->name('merchantStoreProductCopyCount')->option([
            '_alias' => '统计',
        ]);
        Route::post('save', '/save')->name('merchantStoreProductCopySave')->option([
            '_alias' => '保存',
        ]);
    })->prefix('merchant.store.product.ProductCopy')->option([
        '_path' => '/product/list',
        '_auth' => true,
    ]);


    //商品评价管理
    Route::group('store/reply', function () {
        Route::get('lst', '/lst')->name('merchantProductReplyLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('form/:id', '/replyForm')->name('merchantProductReplyForm')->option([
            '_alias' => '回复表单',
        ]);
        Route::post('reply/:id', '/reply')->name('merchantProductReplyReply')->option([
            '_alias' => '回复',
        ]);
    })->prefix('admin.store.StoreProductReply')->option([
        '_path' => '/product/reviews',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/product/reviews',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/product/reviews',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    Route::group('store/reply', function () {
        Route::post('sort/:id', '/changeSort')->name('merchantProductReplySort')->option([
            '_alias' => '排序',
        ]);
    })->prefix('merchant.store.StoreProductReply')->option([
        '_path' => '/product/reviews',
        '_auth' => true,
    ]);;


    //商品标签
    Route::group('product/label', function () {
        Route::get('lst', '/lst')->name('merchantStoreProductLabelLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('create/form', '/createForm')->name('merchantStoreProductLabelCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantStoreProductLabelCreate',
        ]);
        Route::post('create', '/create')->name('merchantStoreProductLabelCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('update/:id/form', '/updateForm')->name('merchantStoreProductLabelUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantStoreProductLabelUpdate',
        ]);
        Route::post('update/:id', '/update')->name('merchantStoreProductLabelUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreProductLabelDetail')->option([
            '_alias' => '详情',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreProductLabelDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('status/:id', '/switchWithStatus')->name('merchantStoreProductLabelStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::get('option', '/getOptions')->option([
            '_alias' => '筛选',
            '_auth' => false,
        ]);

    })->prefix('merchant.store.product.ProductLabel')->option([
        '_path' => '/product/label',
        '_auth' => true,
    ]);


    Route::group('discounts/', function () {
        Route::post('create','/create')->name('merchantStoreDiscountsCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('update/:id','/update')->name('merchantStoreDiscountsUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('lst','/lst')->name('merchantStoreDiscountsLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id','/detail')->name('merchantStoreDiscountsDetail')->option([
            '_alias' => '详情',
        ]);
        Route::delete('delete/:id','/delete')->name('merchantStoreDiscountsDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('status/:id','/switchStatus')->name('merchantStoreDiscountsStatus')->option([
            '_alias' => '修改状态',
        ]);
    })->prefix('merchant.store.product.Discounts')->option([
        '_path' => '/marketing/discounts/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/discounts/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/discounts/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //保障服务
    Route::group('guarantee',function(){
        Route::get('list','/list')->option([
            '_alias' => '列表',
            '_auth' => false,
        ]);
        Route::get('select','/select')->option([
            '_alias' => '筛选',
            '_auth' => false,
        ]);
        Route::get('lst','/lst')->name('merchantGuaranteeLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('create','/create')->name('smerchantGuaranteeCreate')->option([
            '_alias' => '添加',
        ]);
        Route::post('update/:id','/update')->name('merchantGuaranteeUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::get('detail/:id','/detail')->name('merchantGuaranteeDetail')->option([
            '_alias' => '详情',
        ]);
        Route::delete('delete/:id','/delete')->name('merchantGuaranteeDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('sort/:id','/sort')->name('merchantGuaranteeSort')->option([
            '_alias' => '排序',
        ]);
        Route::post('status/:id','/switchStatus')->name('merchantGuaranteeStatus')->option([
            '_alias' => '修改状态',
        ]);
    })->prefix('merchant.store.guarantee.GuaranteeTemplate')->option([
        '_path' => '/config/guarantee',
        '_auth' => true,
    ]);




})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
