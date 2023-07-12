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
use app\common\middleware\LogMiddleware;
use app\common\middleware\AllowOriginMiddleware;
use app\common\middleware\MerchantAuthMiddleware;
use app\common\middleware\MerchantTokenMiddleware;
use app\common\middleware\MerchantCheckBaseInfoMiddleware;

Route::group(function () {

    Route::get('diy/link/getLinks/:id', 'admin.system.diy.PageLink/getLinks')->option([
        '_alias' => '列表',
        '_auth' => false,
    ]);

    Route::get('diy/link/lst', 'admin.system.diy.PageLink/lst')->name('merchantDiyPageLinkLst')->option([
        '_alias' => '列表',
        '_path' => '/devise/diy/list',
    ])->append(['type' => 1]);


    Route::get('diy/categroy/options', 'admin.system.diy.PageCategroy/options')->option([
        '_alias' => '列表 ',
        '_auth' => false,
    ])->append(['type' => 1]);


    Route::group('diy/', function () {

        Route::get('lst', '/lst')->name('merchantDiyLst')->option([
            '_alias' => '列表 ',
        ]);

        Route::get('detail/:id', '/getInfo')->name('merchantDiyLst')->option([
            '_alias' => '列表 ',
        ]);

        Route::post('create/:id', '/saveData')->name('merchantDiyCreate')->option([
            '_alias' => '添加/编辑',
        ]);

        Route::post('status/:id', '/setStatus')->name('merchantDiyStatus')->option([
            '_alias' => '使用模板',
        ]);

        Route::post('set_default_data/:id', '/setDefaultData')->name('merchantDiySetDefault')->option([
            '_alias' => '使用模板',
        ]);

        Route::get('recovery/:id', '/recovery/')->name('merchantDiyRecovery')->option([
            '_alias' => '使用模板',
        ]);

        Route::get('show', '/getDiyInfo')->name('merchantDiyInfo')->option([
            '_alias' => '当前使用模板',
        ]);

        Route::delete('delete/:id', '/del')->name('merchantDiyDelete')->option([
            '_alias' => '删除',
        ]);

        Route::get('product/lst', '/productLst')->name('merchantDiyProductLst')->option([
            '_alias' => '店铺街装修',
        ]);

        Route::get('copy/:id', '/copy')->name('merchantDiyCopy')->option([
            '_alias' => '复制',
        ]);

    })->prefix('admin.system.diy.Diy')->option([
        '_path' => '/devise/diy/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/devise/diy/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/devise/diy/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
