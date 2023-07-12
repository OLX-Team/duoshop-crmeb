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

    //附件管理
    Route::group('system/attachment', function () {
        Route::get('lst', '/getList')->name('merchantAttachmentLst')->option([
            '_alias' => '列表',
            ]);
        Route::delete('delete', '/delete')->name('merchantAttachmentDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('category', '/batchChangeCategory')->name('merchantAttachmentBatchChangeCategory')->option([
            '_alias' => '批量修改',
            ]);
        Route::get('update/:id/form', '/updateForm')->name('merchantAttachmentUpdateForm')->option([
            '_alias' => '编辑表单的',
            '_auth' => false,
            '_form' => 'merchantAttachmentUpdate',
        ]);
        Route::post('update/:id', '/update')->name('merchantAttachmentUpdate')->option([
            '_alias' => '编辑',
            ]);
    })->prefix('admin.system.attachment.Attachment')->option([
        '_path' => '/config/picture',
        '_auth' => true,
    ]);

    //上传图片
    Route::post('upload/image/:id/:field', 'admin.system.attachment.Attachment/image')->name('merchantUploadImage')->option([
        '_path' => 'attachment',
        '_alias' => '上传图片',

    ]);

    //附件分类管理
    Route::group('system/attachment/category', function () {
        Route::get('formatLst', '/getFormatList')->name('merchantAttachmentCategoryGetFormatList')->option([
            '_alias' => '列表',
            ]);
        Route::get('create/form', '/createForm')->name('merchantAttachmentCategoryCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'merchantAttachmentCategoryCreate',
        ]);
        Route::get('update/form/:id', '/updateForm')->name('merchantAttachmentCategoryUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'merchantAttachmentCategoryUpdate',
        ]);
        Route::post('create', '/create')->name('merchantAttachmentCategoryCreate')->option([
            '_alias' => '添加',
            ]);
        Route::post('update/:id', '/update')->name('merchantAttachmentCategoryUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('merchantAttachmentCategoryDelete')->option([
            '_alias' => '删除',
            ]);
    })->prefix('admin.system.attachment.AttachmentCategory')->option([
        '_path' => '/config/picture',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
