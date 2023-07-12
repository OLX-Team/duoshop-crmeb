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

    Route::get('change/color', 'admin.Common/getChangeColor')->name('systemGetChangeColor')->option([
        '_alias' => '一键换色',
        '_path' => '/setting/theme_style',
        '_auth' => false,
        '_form' => 'systemSetChangeColor'
    ]);
    Route::post('change/color', 'admin.Common/setChangeColor')->name('systemSetChangeColor')->option([
        '_alias' => '一键换色保存',
        '_path' => '/setting/theme_style',
        '_auth' => true,
    ]);

    //平台
    Route::group('diy/categroy', function () {

        Route::get('lst', '/lst')->name('systemDiyPageCategroyLst')->option([
            '_alias' => '列表 ',
        ]);

        Route::get('options', '/options')->option([
            '_alias' => '列表 ',
            '_auth' => false,
        ]);

        Route::get('form', '/createForm')->name('systemDiyPageCategroyCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemDiyPageCategroyCreate',
        ]);
        Route::post('create', '/create')->name('systemDiyPageCategroyCreate')->option([
            '_alias' => '添加',
        ]);

        Route::get(':id/form', '/updateForm')->name('systemDiyPageCategroyUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemDiyPageCategroyUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemDiyPageCategroyUpdate')->option([
            '_alias' => '编辑',
        ]);

        Route::post('status/:id', '/switchStatus')->name('systemDiyPageCategroyStatus')->option([
            '_alias' => '编辑状态',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemDiyPageCategroyDelete')->option([
            '_alias' => '删除',
        ]);

    })->prefix('admin.system.diy.PageCategroy')->option([
        '_path' => '/setting/diy/plantform/category/list',
        '_auth' => true,
    ]);

    //商户
    Route::group('diy/mer_categroy', function () {

        Route::get('lst', '/lst')->name('systemDiyPageMerCategroyLst')->option([
            '_alias' => '列表 ',
        ]);
        Route::get('form', '/createForm')->name('systemDiyPageMerCategroyCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemDiyPageMerCategroyCreate',
        ]);
        Route::post('create', '/create')->name('systemDiyPageMerCategroyCreate')->option([
            '_alias' => '添加',
        ]);

        Route::get(':id/form', '/updateForm')->name('systemDiyPageMerCategroyUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemDiyPageMerCategroyUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemDiyPageMerCategroyUpdate')->option([
            '_alias' => '编辑',
        ]);

        Route::post('status/:id', '/switchStatus')->name('systemDiyPageMerCategroyStatus')->option([
            '_alias' => '编辑状态',
        ]);

        Route::delete('delete/:id', '/delete')->name('systemDiyPageMerCategroyDelete')->option([
            '_alias' => '删除',
        ]);
    })->prefix('admin.system.diy.PageCategroy')->option([
        '_path' => '/setting/diy/merchant/category/list',
        '_auth' => true,
    ])->append(['type' => 1]);

    //平台管理
    Route::group('diy/link', function () {
        Route::get('lst', '/lst')->name('systemDiyPageLinkLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('form', '/createForm')->name('systemDiyPageLinkCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemDiyPageLinkCreate',
        ]);
        Route::post('create', '/create')->name('systemDiyPageLinkCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('/:id/form', '/updateForm')->name('systemDiyPageLinkUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemDiyPageLinkUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemDiyPageLinkUpdate')->option([
            '_alias' => '编辑',
        ]);

        Route::delete('delete/:id', '/delete')->name('systemDiyPageLinkDelete')->option([
            '_alias' => '删除',
        ]);

        Route::get('getLinks/:id', '/getLinks')->option([
            '_alias' => '列表',
            '_auth' => false,
        ]);
    })->prefix('admin.system.diy.PageLink')->option([
        '_path' => '/setting/diy/links/list',
        '_auth' => true,
    ]);

    Route::group('diy/mer_link', function () {
        Route::get('lst', '/lst')->name('systemDiyPageLinkMerLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('form', '/createForm')->name('systemDiyPageLinkMerCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemDiyPageLinkMerCreate',
        ]);
        Route::post('create', '/create')->name('systemDiyPageLinkMerCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('/:id/form', '/updateForm')->name('systemDiyPageLinkMerUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemDiyPageLinkMerUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemDiyPageLinkMerUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('delete/:id', '/delete')->name('systemDiyPageLinkMerDelete')->option([
            '_alias' => '删除',
        ]);

    })->prefix('admin.system.diy.PageLink')->option([
        '_path' => '/setting/diy/merLink/list',
        '_auth' => true,
    ])->append(['type' => 1]);


    Route::group('diy/', function () {

        Route::get('lst', 'Diy/lst')->name('systemDiyLst')->option([
            '_alias' => '列表 ',
        ]);

        Route::get('detail/:id', 'Diy/getInfo')->name('systemDiyLst')->option([
            '_alias' => '列表 ',
        ]);

        Route::post('create/:id', 'Diy/saveData')->name('systemDiyCreate')->option([
            '_alias' => '添加/编辑',
        ]);

        Route::post('status/:id', 'Diy/setStatus')->name('systemDiyStatus')->option([
            '_alias' => '使用模板',
        ]);

        Route::post('set_default_data/:id', 'Diy/setDefaultData')->name('systemDiySetDefault')->option([
            '_alias' => '设置默认',
        ]);

        Route::get('recovery/:id', 'Diy/recovery/')->name('systemDiyRecovery')->option([
            '_alias' => '重置',
        ]);

        Route::delete('delete/:id', 'Diy/del')->name('systemDiyDelete')->option([
            '_alias' => '删除',
        ]);
        Route::get('product/lst', 'Diy/productLst')->name('systemDiyProductLst')->option([
            '_alias' => '商品列表',
        ]);
        Route::get('copy/:id', 'Diy/copy')->name('systemDiyCopy')->option([
            '_alias' => '复制',
        ]);

        Route::get('user_index', 'VisualConfig/userIndex')->name('systemVisualUserInfo')->option([
            '_alias' => '个人中心装修',
        ]);
        Route::post('user_index', 'VisualConfig/setUserIndex')->name('systemVisualUserInfoSave')->option([
            '_alias' => '个人中心装修',
            '_auth' => false,
            '_form' => 'systemVisualUserInfo',
        ]);
        Route::get('store_street', 'VisualConfig/storeStreet')->name('systemVisualStoreStreet')->option([
            '_alias' => '店铺街装修',
        ]);
        Route::post('store_street', 'VisualConfig/setStoreStreet')->name('systemVisualStoreStreetSave')->option([
            '_alias' => '店铺街装修',
            '_auth' => false,
            '_form' => 'systemVisualStoreStreet',
        ]);
        Route::get('select', 'Diy/select');
    })->prefix('admin.system.diy.')->option([
        '_path' => '/setting/diy/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/setting/diy/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/setting/diy/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    Route::group('diy/', function () {
        Route::get('get_theme_key', 'VisualConfig/getThemeKey')->name('systemVisualStoreGetThemeKey')->option([
            '_alias' => '可视化列表',
        ]);
        Route::get('get_theme/:key', 'VisualConfig/getTheme')->name('systemVisualStoreGetTheme')->option([
            '_alias' => '可视化详情',
        ]);
        Route::post('set_theme/:key', 'VisualConfig/setTheme')->name('systemVisualSetTheme')->option([
            '_alias' => '可视化保存',
        ]);
    })->prefix('admin.system.diy.')->option([
        '_path' => '/setting/system_visualization_data',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/setting/diy/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/setting/diy/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    Route::group('micro/', function () {

        Route::get('lst', 'Diy/lst')->name('systemDiyMicroLst')->option([
            '_alias' => '列表 ',
        ]);
        Route::get('detail/:id', 'Diy/getInfo')->name('systemDiyMicroDetail')->option([
            '_alias' => '详情 ',
        ]);
        Route::post('create/:id', 'Diy/saveData')->name('systemDiyMicroCreate')->option([
            '_alias' => '添加/编辑',
        ]);
        Route::get('recovery/:id', 'Diy/recovery/')->name('systemDiyMicroRecovery')->option([
            '_alias' => '重置',
        ]);
        Route::delete('delete/:id', 'Diy/del')->name('systemDiyMicroDelete')->option([
            '_alias' => '删除',
        ]);
    })->prefix('admin.system.diy.')->option([
        '_path' => '/setting/micro/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/setting/micro/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/setting/micro/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ])
        ->append(['is_diy' => 0]);

    })->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
