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
use app\common\middleware\MerchantCheckBaseInfoMiddleware;
use app\common\middleware\MerchantTokenMiddleware;
use think\facade\Route;

Route::group(function () {

    Route::group('group', function () {
        Route::get('detail/:id', '/get')->name('merchantGroupDetail')->option([
            '_alias' => '数据详情',
        ]);
        Route::get('data/lst/:groupId', 'Data/lst')->name('merchantGroupDataLst')->option([
            '_alias' => '数据列表',
        ]);

        Route::get('data/create/table/:groupId', 'Data/createTable')->name('merchantGroupDataCreateForm')->option([
            '_alias' => '数据添加表单',
            '_auth' => false,
            '_form' => 'groupDataCreate',
        ]);
        Route::post('data/create/:groupId', 'Data/create')->name('merchantGroupDataCreate')->option([
            '_alias' => '数据添加',
        ]);

        Route::get('data/update/table/:groupId/:id', 'Data/updateTable')->name('merchantGroupDataUpdateForm')->option([
            '_alias' => '数据编辑表单',
            '_auth' => false,
            '_form' => 'groupDataUpdate',
        ]);
        Route::post('data/update/:groupId/:id', 'Data/update')->name('merchantGroupDataUpdate')->option([
            '_alias' => '数据编辑',
            ]);
        Route::delete('data/delete/:id', 'Data/delete')->name('merchantGroupDataDelete')->option([
            '_alias' => '数据删除',
        ]);
        Route::post('data/status/:id', 'Data/changeStatus')->name('merchantGroupDataChangeStatus')->option([
            '_alias' => '数据修改状态',
        ]);
    })->prefix('admin.system.groupData.Group')->option([
        '_auth' => true,
        '_init'  => [ \crmeb\services\UpdateAuthInit::class,'groupData'],
        '_append' => [
            [
                '_name' => 'uploadImage',
                '_alias' => '上传图片',
                '_auth' => true,
            ],
            [
                '_name' => 'systemAttachmentLst',
                '_alias' => '图片列表',
                '_auth' => true,
            ],
        ]
    ]);

})->middleware(AllowOriginMiddleware::class)
    ->middleware(MerchantTokenMiddleware::class, true)
    ->middleware(MerchantAuthMiddleware::class)
    ->middleware(MerchantCheckBaseInfoMiddleware::class)
    ->middleware(LogMiddleware::class);
