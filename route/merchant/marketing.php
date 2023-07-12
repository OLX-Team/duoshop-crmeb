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

    //秒杀商品
    Route::group('store/seckill_product', function () {
        Route::get('lst_time', '/lst_time')->option([
            '_alias' => '时间配置',
            '_auth'  => false,
        ]);
        Route::get('lst_filter', '/getStatusFilter')->name('merchantStoreSeckillProductLstFilter')->option([
            '_alias' => '统计',
        ]);
        Route::get('lst', '/lst')->name('merchantStoreSeckillProductLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('create', '/create')->name('merchantStoreSeckillProductCreate')->option([
            '_alias' => '添加 ',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreSeckillProductDetail')->option([
            '_alias' => '详情',
        ]);
        Route::post('update/:id', '/update')->name('merchantStoreSeckillProductUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreSeckillProductDelete')->option([
            '_alias' => '删除',
        ]);
        Route::delete('destory/:id', '/destory')->name('merchantStoreSeckillProductDestory')->option([
            '_alias' => '彻底删除',
        ]);
        Route::post('restore/:id', '/restore')->name('merchantStoreSeckillProductRestore')->option([
            '_alias' => '恢复',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantStoreSeckillProductSwitchStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('sort/:id', '/updateSort')->name('merchantStoreSeckillProductUpdateSort')->option([
            '_alias' => '排序',
        ]);
        Route::post('preview', '/preview')->name('merchantStoreSeckillProductPreview')->option([
            '_alias' => '预览',
        ]);
        Route::post('labels/:id', '/setLabels')->name('merchantStoreSeckillProductLabels')->option([
            '_alias' => '设置标签',
        ]);
    })->prefix('merchant.store.product.ProductSeckill')->option([
        '_path' => '/marketing/seckill/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/seckill/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/seckill/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //预售商品
    Route::group('store/product/presell', function () {
        Route::get('lst', '/lst')->name('merchantStoreProductPresellLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('create', '/create')->name('merchantStoreProductPresellCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreProductPresellDetail')->option([
            '_alias' => '详情',
        ]);
        Route::post('update/:id', '/update')->name('merchantStoreProductPresellUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreProductPresellDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantStoreProductPresellStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::get('number', '/number')->option([
            '_alias' => '统计',
            '_auth'  => false,
        ]);
        Route::post('sort/:id', '/updateSort')->name('merchantStoreProductPresellUpdateSort')->option([
            '_alias' => '排序',
        ]);
        Route::post('preview', '/preview')->name('merchantStoreProductPresellPreview')->option([
            '_alias' => '预览',
        ]);
        Route::post('labels/:id', '/setLabels')->name('merchantStoreProductPreselltLabels')->option([
            '_alias' => '设置标签',
        ]);
    })->prefix('merchant.store.product.ProductPresell')->option([
        '_path' => '/marketing/presell/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/presell/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/presell/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //助力商品
    Route::group('store/product/assist', function () {
        Route::get('lst', '/lst')->name('merchantStoreProductAssistLst')->option([
            '_alias' => '列表 ',
        ]);
        Route::post('create', '/create')->name('merchantStoreProductAssistCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreProductAssistDetail')->option([
            '_alias' => '详情',
        ]);
        Route::post('update/:id', '/update')->name('merchantStoreProductAssistUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreProductAssistDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantStoreProductAssistStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('sort/:id', '/updateSort')->name('merchantStoreProductAssistUpdateSort')->option([
            '_alias' => '排序',
        ]);
        Route::post('preview', '/preview')->name('merchantStoreProductAssistPreview')->option([
            '_alias' => '预览',
        ]);
        Route::post('labels/:id', '/setLabels')->name('merchantStoreProductAssistLabels')->option([
            '_alias' => '设置标签',
        ]);
    })->prefix('merchant.store.product.ProductAssist')->option([
        '_path' => '/marketing/assist/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/assist/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/assist/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //助力活动
    Route::group('store/product/assist_set', function () {
        Route::get('lst', '/lst')->name('merchantStoreProductAssistSetLst')->option([
            '_alias' => '活动列表',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreProductAssistSetDetail')->option([
            '_alias' => '活动详情',
        ]);
    })->prefix('merchant.store.product.ProductAssistSet')->option([
        '_path' => '/marketing/assist/assist_set',
        '_auth' => true,
    ]);

    //拼团商品
    Route::group('store/product/group', function () {
        Route::get('lst', '/lst')->name('merchantStoreProductGroupLst')->option([
            '_alias' => '列表',
        ]);
        Route::post('create', '/create')->name('merchantStoreProductGroupCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreProductGroupDetail')->option([
            '_alias' => '详情',
        ]);
        Route::post('update/:id', '/update')->name('merchantStoreProductGroupUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantStoreProductGroupDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('status/:id', '/switchStatus')->name('merchantStoreProductGroupStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('sort/:id', '/updateSort')->name('merchantStoreProductGroupSort')->option([
            '_alias' => '排序',
        ]);
        Route::post('preview', '/preview')->name('merchantStoreProductGroupPreview')->option([
            '_alias' => '预览',
        ]);
        Route::post('labels/:id', '/setLabels')->name('merchantStoreProductGroupLabels')->option([
            '_alias' => '设置标签',
        ]);
    })->prefix('merchant.store.product.ProductGroup')->option([
        '_path' => '/marketing/combination/combination_goods',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/combination/combination_goods',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/combination/combination_goods',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    Route::get('config/others/group_buying', 'admin.system.config.ConfigOthers/getGroupBuying')
        ->name('merchantConfigGroupBuying')->option([
            '_alias' => '拼团配置',
            '_path' => '/marketing/combination/combination_goods',
            '_auth' => true,
        ]);;

    //拼团活动
    Route::group('store/product/group/buying', function () {
        Route::get('lst', '/lst')->name('merchantStoreProductGroupBuyingLst')->option([
            '_alias' => '活动列表 ',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantStoreProductGroupBuyingDetail')->option([
            '_alias' => '活动详情',
        ]);
    })->prefix('merchant.store.product.ProductGroupBuying')->option([
        '_path' => '/marketing/combination/combination_list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/combination/combination_list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/combination/combination_list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);



    //直播间
    Route::group('broadcast/room', function () {
        Route::get('lst', '/lst')->name('merchantBroadcastRoomLst')->option([
            '_alias' => '列表 ',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantBroadcastRoomDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('create/form', '/createForm')->name('merchantBroadcastRoomCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantBroadcastRoomCreate',
        ]);
        Route::post('create', '/create')->name('merchantBroadcastRoomCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('merchantBroadcastRoomUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantBroadcastRoomUpdate',
        ]);
        Route::post('update/:id', '/update')->name('merchantBroadcastRoomUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('status/:id', '/changeStatus')->name('merchantBroadcastRoomChangeStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('export_goods', '/exportGoods')->name('merchantBroadcastRoomExportGoods')->option([
            '_alias' => '导入商品',
        ]);
        Route::post('rm_goods', '/rmExportGoods')->name('merchantBroadcastRoomRmExportGoods')->option([
            '_alias' => '删除商品',
        ]);
        Route::post('mark/:id', '/mark')->name('merchantBroadcastRoomMark')->option([
            '_alias' => '备注',
        ]);
        Route::get('goods/:id', '/goodsList')->name('merchantBroadcastRoomGoods')->option([
            '_alias' => '商品详情',
        ]);

        Route::post('closeKf/:id', '/closeKf')->name('merchantBroadcastRoomCloseKf')->option([
            '_alias' => '关闭客服',
        ]);
        Route::post('comment/:id', '/banComment')->name('merchantBroadcastRoomCloseComment')->option([
            '_alias' => '禁言',
        ]);
        Route::post('feedsPublic/:id', '/isFeedsPublic')->name('merchantBroadcastRoomCloseFeeds')->option([
            '_alias' => '收录',
        ]);
        Route::post('on_sale/:id', '/onSale')->name('merchantBroadcastOnSale')->option([
            '_alias' => '商品上下架',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantBroadcastRoomDelete')->option([
            '_alias' => '删除',
        ]);
        Route::get('addassistant/form/:id', '/addAssistantForm')->name('merchantBroadcastAddAssistantForm')->option([
            '_alias' => '添加客服表单',
            '_auth' => false,
            '_form' => 'merchantBroadcastAddAssistant',
        ]);
        Route::post('addassistant/:id', '/addAssistant')->name('merchantBroadcastAddAssistant')->option([
            '_alias' => '添加 客服',
        ]);
        Route::get('push_message/:id', '/pushMessage')->name('merchantBroadcastPushMessage')->option([
            '_alias' => '消息推送',
        ]);

    })->prefix('merchant.store.broadcast.BroadcastRoom')->option([
        '_path' => '/marketing/studio/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/studio/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/studio/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //直播小助手
    Route::group('broadcast/assistant', function () {
        Route::get('lst', '/lst')->name('merchantBroadcastAssistantLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('create/form', '/createForm')->name('merchantBroadcastAssistantCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantBroadcastAssistantCreate',
        ]);
        Route::post('create', '/create')->name('merchantBroadcastAssistantCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('update/:id/form', '/updateForm')->name('merchantBroadcastAssistantUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantBroadcastAssistantUpdate',
        ]);
        Route::post('update/:id', '/update')->name('merchantBroadcastAssistantUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('mark/:id', '/mark')->name('merchantBroadcastAssistantMark')->option([
            '_alias' => '备注',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantBroadcastAssistantDelete')->option([
            '_alias' => '删除',
        ]);
    })->prefix('merchant.store.broadcast.BroadcastAssistant')->option([
        '_path' => '/marketing/studio/assistant',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/studio/assistant',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/studio/assistant',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //直播间商品
    Route::group('broadcast/goods', function () {
        Route::get('lst', '/lst')->name('merchantBroadcastGoodsLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('detail/:id', '/detail')->name('merchantBroadcastGoodsDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('create/form', '/createForm')->name('merchantBroadcastGoodsCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantBroadcastGoodsCreate',
        ]);
        Route::post('create', '/create')->name('merchantBroadcastGoodsCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('merchantBroadcastGoodsUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantBroadcastGoodsUpdate',
        ]);
        Route::post('update/:id', '/update')->name('merchantBroadcastGoodsUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('status/:id', '/changeStatus')->name('merchantBroadcastGoodsChangeStatus')->option([
            '_alias' => '修改状态',
        ]);
        Route::post('mark/:id', '/mark')->name('merchantBroadcastGoodsMark')->option([
            '_alias' => '备注',
        ]);
        Route::delete('delete/:id', '/delete')->name('merchantBroadcastGoodsDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('batch_create', '/batchCreate')->name('merchantBroadcastGoodsbatchCreate')->option([
            '_alias' => '批量添加',
        ]);
    })->prefix('merchant.store.broadcast.BroadcastGoods')->option([
        '_path' => '/marketing/studio/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantUploadImage',
                '_path'  =>'/marketing/studio/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantAttachmentLst',
                '_path'  =>'/marketing/studio/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //积分
    Route::group('integral',function(){
        Route::get('lst','/getList')->name('merchantIntegralList')->option([
            '_alias' => '列表',
        ]);
        Route::get('title','/getTitle')->name('merchantIntegralTitle')->option([
            '_alias' => '统计',
        ]);
    })->prefix('merchant.user.UserIntegral')->option([
        '_path' => '/marketing/integral/log',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'merchantConfigForm',
                '_path'  =>'/marketing/integral/log',
                '_alias' => '配置获取',
                '_auth'  => true,
            ],
            [
                '_name'  =>'merchantConfigSave',
                '_path'  =>'/marketing/integral/log',
                '_alias' => '配置保存',
                '_auth'  => true,
            ],

        ]
    ]);
})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
