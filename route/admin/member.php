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

    //付费会员等级
    Route::group('user/svip', function () {
        Route::get('type/lst', 'admin.user.Svip/getTypeLst')->name('systemUserSvipLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('type/form', 'admin.user.Svip/createTypeCreateForm')->name('systemUserSvipCreate')->option([
            '_alias' => '添加',
        ]);
        Route::get('type/:id/form', 'admin.user.Svip/updateTypeCreateForm')->name('systemUserSvipUpdateForm')->option([
            '_alias' => '编辑表单',
        ]);
        Route::post('update/:groupId/:id', 'admin.system.groupData.GroupData/update')->name('systemUserSvipTypeUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::delete('type/delete/:id', 'admin.system.groupData.GroupData/delete')->name('systemUserSvipDelete')->option([
            '_alias' => '删除',
        ]);
        Route::post('type/status/:id', 'admin.system.groupData.GroupData/changeStatus')->name('systemUserSvipStatus')->option([
            '_alias' => '修改状态',
        ]);
    })->append(['type' => 1])->option([
        '_path' => '/user/member/type',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/user/member/type',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/user/member/type',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);
    Route::get('user/svip/order_lst', 'admin.user.Svip/payList')->name('systemUserSvipPayLst')->option([
        '_alias' => '列表',
        '_path' => '/user/member/record',
    ]);

    /**
     * 付费会员权益
     */
    Route::group('svip/interests', function () {
        Route::get('lst', '/getSvipInterests')->name('systemUserSvipInterestsLst')->option([
            '_alias' => '列表',
        ]);
        Route::get(':id/form', '/updateSvipForm')->name('systemUserSvipInterestsUpdateForm')->option([
            '_alias' => '编辑',
            '_auth'  => false,
            '_form' => 'systemUserSvipInterestsUpdate',
        ]);
        Route::post('update/:id', '/update')->name('systemUserSvipInterestsUpdate')->option([
            '_alias' => '编辑',
        ]);
        Route::post('status/:id', '/switchWithStatus')->name('systemUserSvipInterestsStatus')->option([
            '_alias' => '编辑状态',
        ]);
    })->prefix('admin.user.MemberInterests')->append(['type' => 2])->option([
        '_path' => '/user/member/equity',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/user/member/equity',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/user/member/equity',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);


    //普通会员等级
    Route::group('user/member', function () {
        Route::get('lst', '.UserBrokerage/getLst')->name('systemUserMemberLst')->option([
            '_alias' => '普通会员等级列表',
        ]);
        Route::get('detail/:id', '.UserBrokerage/detail')->option([
            '_alias' => '普通会员等级详情',
            '_auth'  => false,
        ]);
        Route::get('options', '.UserBrokerage/options')->option([
            '_alias' => '普通会员等级筛选',
            '_auth'  => false,
        ]);
        Route::get('create/form', '.UserBrokerage/createForm')->name('systemUserMemberCreateForm')->option([
            '_alias' => '普通会员等级添加表单',
            '_auth' => false,
            '_form' => 'systemUserMemberCreate',
        ]);
        Route::post('create', '.UserBrokerage/create')->name('systemUserMemberCreate')->option([
            '_alias' => '普通会员等级添加',
            ]);
        Route::get('update/:id/form', '.UserBrokerage/updateForm')->name('systemUserMemberUpdateForm')->option([
            '_alias' => '普通会员等级编辑表单',
            '_auth' => false,
            '_form' => 'systemUserMemberUpdate',
        ]);
        Route::post('update/:id', '.UserBrokerage/update')->name('systemUserMemberUpdate')->option([
            '_alias' => '普通会员等级编辑',
            ]);
        Route::delete('delete/:id', '.UserBrokerage/delete')->name('systemUserMemberDelete')->option([
            '_alias' => '普通会员等级删除',
            ]);
    })->prefix('admin.user')->append(['type' => 1])->option([
        '_path' => '/user/member/list',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/user/member/list',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/user/member/list',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);

    //普通会员权益
    Route::group('member/interests', function () {
        Route::get('lst', '.MemberInterests/getLst')->name('systemUserMemberInterestsLst')->option([
            '_alias' => '会员权益',
            ]);
        Route::get('detail/:id', '.MemberInterests/detail')->option([
            '_alias' => '会员权益详情',
            '_auth'  => false,
        ]);
        Route::get('options', '.MemberInterests/options')->option([
            '_alias' => '会员权益筛选',
            '_auth'  => false,
        ]);
        Route::get('create/form', '.MemberInterests/createForm')->name('systemUserMemberInterestsCreateForm')->option([
            '_alias' => '会员权益添加表单',
            '_auth' => false,
            '_form' => 'systemUserMemberInterestsCreate',
        ]);
        Route::post('create', '.MemberInterests/create')->name('systemUserMemberInterestsCreate')->option([
            '_alias' => '会员权益添加',
            ]);
        Route::get('update/:id/form', '.MemberInterests/updateForm')->name('systemUserMemberInterestsUpdateForm')->option([
            '_alias' => '会员权益编辑表单',
            '_auth' => false,
            '_form' => 'systemUserMemberInterestsUpdate',
        ]);
        Route::post('update/:id', '.MemberInterests/update')->name('systemUserMemberInterestsUpdate')->option([
            '_alias' => '会员权益编辑',
            ]);
        Route::delete('delete/:id', '.MemberInterests/delete')->name('systemUserMemberInterestsDelete')->option([
            '_alias' => '会员权益删除',
            ]);
    })->prefix('admin.user')->append(['type' => 1])->option([
        '_path' => '/user/member/interests',
        '_auth' => true,
        '_append'=> [
            [
                '_name'  =>'uploadImage',
                '_path'  =>'/user/member/interests',
                '_alias' => '上传图片',
                '_auth'  => true,
            ],
            [
                '_name'  =>'systemAttachmentLst',
                '_path'  =>'/user/member/interests',
                '_alias' => '图片列表',
                '_auth'  => true,
            ],
        ]
    ]);


})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
