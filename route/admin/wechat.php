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
    Route::group('config/setting', function () {
        Route::post('upload_file/:field', '/upload')->name('configUpload')->option([
            '_alias' => '上传文件',
        ]);

        Route::post('update_name/:field', '/uploadAsName')->name('configUploadName')->option([
            '_alias' => '上传原名文件',
        ]);

        Route::get('wechat/file/form', '/uploadWechatForm')->name('configWechatUploadForm')->option([
            '_alias' => '微信校验文件上传表单',
            '_auth' => false,
            '_form' => 'configWechatUploadSet'
        ]);

        Route::post('wechat_set', '/uploadWechatSet')->name('configWechatUploadSet')->option([
            '_alias' => '微信校验文件上传',
        ]);

        Route::get('routine/config', '/getRoutineConfig')->name('configRoutineConfig')->option([
            '_alias' => '小程序配置',
        ]);

        Route::get('routine/downloadTemp', '/downloadTemp')->name('configRoutineDownload')->option([
            '_alias' => '小程序下载',
            '_path' => '/app/routine/download',
        ]);

    })->prefix('admin.system.config.Config')->option([
        '_path' => '/app/wechat/file',
        '_auth' => true,
    ]);

    //微信菜单
    Route::group('wechat', function () {
        Route::get('menu', '/info')->name('wechatMenu')->option([
            '_alias' => '微信菜单配置',
        ]);
        Route::post('menu', '/save')->name('saveWechatMenu')->option([
            '_alias' => '保存微信菜单配置',
        ]);
    })->prefix('admin.wechat.WechatMenu')->option([
        '_path' => '/app/wechat/menus',
        '_auth' => true,
    ]);

    //自动回复
    Route::group('wechat/reply', function () {
        Route::get('detail/:id', '/info')->name('wechatReplyInfo')->option([
            '_alias' => '详情',
            ]);
        Route::post('save/:key', '/save')->name('saveWechatReply')->option([
            '_alias' => '编辑',
            ]);
        Route::post('create', '/create')->name('createWechatReply')->option([
            '_alias' => '添加',
            ]);
        Route::post('update/:id', '/update')->name('updateWechatReply')->option([
            '_alias' => '修改',
            ]);
        Route::get('lst', '/lst')->name('wechatReplyLst')->option([
            '_alias' => '列表',
            ]);
        Route::delete(':id', '/delete')->name('wechatReplyDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('status/:id', '/changeStatus')->name('wechatReplyStatus')->option([
            '_alias' => '修改状态',
            ]);
        Route::post('upload/image', '/uploadImage')->name('wechatUploadImage')->option([
            '_alias' => '上传图片',
            ]);
        Route::post('upload/voice', '/uploadVoice')->name('wechatUploadVoice')->option([
            '_alias' => '上传语音',
            ]);
    })->prefix('admin.wechat.WechatReply')->option([
        '_path' => '/admin/app/wechat/reply',
        '_auth' => true,
    ]);

    //图文管理
    Route::group('wechat/news', function () {
        Route::post('create', '/create')->name('systemWechatNewsCreate')->option([
            '_alias' => '添加',
            ]);
        Route::post('update/:id', '/update')->name('systemWechatNewsUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemWechatNewsDelete')->option([
            '_alias' => '删除',
            ]);
        Route::get('lst', '/lst')->name('systemWechatNewsLst')->option([
            '_alias' => '列表',
            ]);

        Route::post('update/:id', '/update')->name('systemWechatNewsUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemWechatNewsDelete')->option([
            '_alias' => '删除',
            ]);
        Route::get('detail/:id', '/detail')->name('systemWechatNewsDetail')->option([
            '_alias' => '详情',
            ]);
    })->prefix('admin.wechat.WechatNews')->option([
        '_path' => '/app/wechat/newsCategory',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/app/wechat/newsCategory',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/app/wechat/newsCategory',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);


    //微信消息模板
    Route::group('wechat/template', function () {
        Route::get('sync', '/sync')->name('systemTemplateMessageSync')->append(['type' => 1])->option([
            '_alias' => '同步',
            ]);
        Route::get('lst', '/lst')->name('systemTemplateMessageLst')->option([
            '_alias' => '列表',
            ]);
        Route::get('create/form', '/createform')->name('systemTemplateMessageCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemTemplateMessageCreate',
        ]);
        Route::post('create', '/create')->name('systemTemplateMessageCreate')->option([
            '_alias' => '添加',
            ]);
        Route::get('update/:id/form', '/updateForm')->name('systemTemplateMessageUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemTemplateMessageUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemTemplateMessageUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemTemplateMessageDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('status/:id', '/switchStatus')->name('systemTemplateMessageSwitchStatus')->option([
            '_alias' => '修改状态',
            ]);
    })->prefix('admin.wechat.TemplateMessage')->option([
        '_path' => '/app/wechat/template',
        '_auth' => true,
    ]);

    //小程序订阅消息
    Route::group('wechat/template/min', function () {
        Route::get('/sync', '/sync')->name('systemTemplateMessageMinSync')->append(['type' => 0])->option([
            '_alias' => '同步',
            ]);
        Route::get('lst', '/minList')->name('systemTemplateMessageMinList')->option([
            '_alias' => '列表 ',
            ]);
        Route::get('create/form', '/createMinform')->name('systemTemplateMessageMinCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => 'systemTemplateMessageMinCreate',
        ]);
        Route::post('create', '/create')->name('systemTemplateMessageMinCreate')->option([
            '_alias' => '添加',
            ]);
        Route::get('update/:id/form', '/updateForm')->name('systemTemplateMessageMinUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => 'systemTemplateMessageMinUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemTemplateMessageMinUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemTemplateMessageMinDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('status/:id', '/switchStatus')->name('systemTemplateMessageMinSwitchStatus')->option([
            '_alias' => '修改状态',
            ]);
    })->prefix('admin.wechat.TemplateMessage')->option([
        '_path' => '/app/routine/template',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
