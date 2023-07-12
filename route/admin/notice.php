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

    //系统公告
    Route::group('notice', function () {
        Route::get('lst', '/lst')->name('systemNoticeList')->option([
            '_alias' => '系统公告列表',
        ]);
        Route::post('create', '/create')->name('systemNoticeCreate')->option([
            '_alias' => '系统公告发布',
        ]);
    })->prefix('admin.system.notice.SystemNotice')->option([
        '_path' => '/station/notice',
        '_auth' => true,
    ]);

    Route::group('notice/config', function () {
        Route::get('lst', '/lst')->name('systemNoticeConfigLst')->option([
            '_alias' => '消息配置列表',
            ]);
        Route::get('create/form', '/createForm')->name('systemNoticeConfigCreateForm')->option([
            '_alias' => '消息配置添加表单',
            '_auth' => false,
            '_form' => 'systemNoticeConfigCreate',
        ]);
        Route::post('create', '/create')->name('systemNoticeConfigCreate')->option([
            '_alias' => '消息配置添加',
            ]);
        Route::get('update/:id/form', '/updateForm')->name('systemNoticeConfigUpdateForm')->option([
            '_alias' => '消息配置编辑表单',
            '_auth' => false,
            '_form' => 'systemNoticeConfigUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemNoticeConfigUpdate')->option([
            '_alias' => '消息配置编辑',
            ]);
        Route::get('detail/:id', '/detail')->name('systemNoticeConfigDetail')->option([
            '_alias' => '消息配置详情',
            ]);
        Route::delete('delete/:id', '/delete')->name('systemNoticeConfigDelete')->option([
            '_alias' => '消息配置删除',
            ]);
        Route::post('status/:id', '/switchStatus')->name('systemNoticeConfigStatus')->option([
            '_alias' => '消息配置修改状态',
            ]);
        Route::get('change/:id/form', '/getTemplateId')->name('systemNoticeConfigGetChangeTempId')->option([
            '_alias' => '消息配置修改模板ID',
        ]);
        Route::post('change/:id/save', '/setTemplateId')->name('systemNoticeConfigSetChangeTempId')->option([
            '_alias' => '消息配置修改模板ID',
        ]);
        Route::get('option', '/getOptions')->option([
            '_alias' => '消息配置筛选',
            '_auth'  => false,
        ]);
    })->prefix('admin.system.notice.SystemNoticeConfig')->option([
        '_path' => '/setting/notification/index',
        '_auth' => true,
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
