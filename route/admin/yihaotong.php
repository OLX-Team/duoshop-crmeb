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

    //复制商品
    Route::group('serve', function () {
        Route::get('us_lst', '/lst')->name('systemStoreProductCopyLst')->option([
            '_alias' => '使用记录',
            '_path' => '/setting/sms/sms_config/index',
        '_auth'  => true,
        ]);
    })->prefix('merchant.store.product.ProductCopy');

    Route::group('sms', function () {
        Route::get('record', '.Sms/record')->name('smsRecord')->option([
            '_alias' => '短信发送记录',
        ]);
        Route::get('logout', '.Sms/logout')->name('smsLogout')->option([
            '_alias' => '退出登录',
        ]);
    })->prefix('admin.system.sms')->option([
        '_auth' => true,
        '_path' => '/setting/sms/sms_config/index',
    ]);

    //一号通
    Route::group('serve', function () {
        Route::get('captcha/:phone', 'Login/captcha')->name('systemServeCaptcha')->option([
            '_alias' => '获取验证码',
        ]);
        Route::post('captcha', 'Login/checkCode')->name('systemServeCaptchaCheck')->option([
            '_alias' => '验证码校验',
        ]);
        Route::post('register', 'Login/register')->name('systemServeRegister')->option([
            '_alias' => '注册',
        ]);
        Route::post('login', 'Login/login')->name('systemServeLogin')->option([
            '_alias' => '登录',
        ]);
        Route::post('change_password', 'Serve/changePassword')->name('systemServeChangePassword')->option([
            '_alias' => '修改密码',
            ]);
        Route::post('change_phone', 'Serve/updatePhone')->name('systemServeChangePhone')->option([
            '_alias' => '修改手机号',
        ]);
        Route::get('user/is_login', 'Serve/is_login')->name('systemServeIsLogin')->option([
            '_alias' => '检测登录状态',
        ]);
        Route::get('user/info', 'Serve/getUserInfo')->name('systemServeUserInfo')->option([
            '_alias' => '账号信息',
            '_auth' => false,
        ]);
        Route::get('record', 'Serve/getRecord')->name('systemServeRecordLst')->option([
            '_alias' => '使用记录',
        ]);
        Route::get('mealList/:type', 'Serve/mealList')->name('systemServeMealLst')->option([
            '_alias' => '套餐列表',
        ]);
        Route::get('paymeal', 'Serve/payMeal')->name('systemServePayMeal')->option([
            '_alias' => '购买套餐',
        ]);
        Route::post('open', 'Serve/openServe')->name('systemServeOpenServe')->option([
            '_alias' => '开通服务',
        ]);
        Route::post('change_sign', 'Sms/changeSign')->name('systemServeChangeSign')->option([
            '_alias' => '修改签名',
        ]);
        //
        Route::get('paylst', 'Serve/paylst')->name('systemServePayLst')->option([
            '_alias' => '购买记录',
            '_path' => '/service/purchase',
            ]);
        Route::get('mer/paylst', 'Serve/merPaylst')->name('systemServeMerPayLst')->option([
            '_alias' => '商户购买记录',
            '_path' => '/service/purchase',
            ]);

        Route::get('mer/lst', 'Serve/merlst')->name('systemServeMerLst')->option([
            '_alias' => '商户结余',
            '_path' => '/service/balance_record',
        ]);

    })->prefix('admin.system.serve.')->option([
        '_path' => '/setting/sms/sms_config/index',
        '_auth' => true,
    ]);

    //配置套餐
    Route::group('serve', function () {
        Route::get('meal/lst', 'Config/lst')->name('systemServeMerMealLst')->option([
            '_alias' => '列表',
        ]);
        Route::get('meal/detail/:id', 'Config/detail')->name('systemServeMealDetail')->option([
            '_alias' => '详情',
        ]);
        Route::get('meal/create/form', 'Config/createForm')->name('systemServeMealCreateForm')->option([
            '_alias' => '添加表单',
            '_auth' => false,
            '_form' => "systemServeMealCreate"
        ]);
        Route::post('meal/create', 'Config/create')->name('systemServeMealCreate')->option([
            '_alias' => '添加',
            ]);
        Route::get('meal/update/:id/form', 'Config/updateForm')->name('systemServeMealUpdateForm')->option([
            '_alias' => '编辑表单',
            '_auth' => false,
            '_form' => "systemServeMealUpdate"
        ]);
        Route::post('meal/update/:id', 'Config/update')->name('systemServeMealUpdate')->option([
            '_alias' => '编辑',
            ]);
        Route::delete('meal/detele/:id', 'Config/detele')->name('systemServeMealDelete')->option([
            '_alias' => '删除',
            ]);
        Route::post('meal/status/:id', 'Config/switchStatus')->name('systemServeMealStatus')->option([
            '_alias' => '修改状态',
            ]);
    })->prefix('admin.system.serve.')->option([
        '_path' => '/service/settings',
        '_auth' => true,
    ]);

    Route::group('serve', function () {
        Route::get('expr/lst', 'Export/getExportAll')->name('systemServeExportLst')->option([
            '_alias' => '列表',
            '_path' => '/freight/express',
            ]);
        Route::get('expr/temps', 'Export/getExportTemp')->name('systemServeExportTemps')->option([
            '_alias' => '模板',
            '_path' => '/setting/sms/sms_config/index',
            ]);
        Route::get('expr/dump_lst', 'Export/dumpLst')->name('systemServeExportDumpLst')->option([
            '_alias' => '使用记录',
            '_path' => '/setting/sms/sms_config/index',
            ]);

        Route::get('sms/temps', 'Sms/temps')->name('systemServeSmsTemps')->option([
            '_alias' => '短信模板',
            '_path' => '/sms/template',
            ]);
        Route::post('sms/apply', 'Sms/apply')->name('systemServeSmsApply')->option([
            '_alias' => '申请模板',
            '_path' => '/sms/template',
            ]);
        Route::get('sms/apply_record', 'Sms/applyRecord')->name('systemServeSmsApplyRecord')->option([
            '_alias' => '申请记录',
            '_path' => '/sms/applyList',
            ]);
    })->prefix('admin.system.serve.')->option([
        '_auth' => true,
    ]);



//    Route::group('sms', function () {
//        //保存配置 登录
//        Route::post('config', '.Sms/save_basics')->name('smsLogin');
//        //短信发送记录
//        Route::get('record', '.Sms/record')->name('smsRecord');
//        //短信账号数据
//        Route::get('data', '.Sms/data')->name('smsData');
//        //查看是否登录
//        Route::get('is_login', '.Sms/is_login');
//        //退出登录
//        Route::get('logout', '.Sms/logout')->name('smsLogout');
//        //发送短信验证码
//        Route::post('captcha', '.Sms/captcha')->name('smsCaptcha');
//        //修改/注册短信平台账号
//        Route::post('register', '.Sms/save')->name('smsSave');
//        //短信模板列表
//        Route::get('temp', '.SmsTemplate/template')->name('smsTemplate');
//        //短信模板申请表单
//        Route::get('temp/form', '.SmsTemplate/form')->name('smsCreateForm');
//        //短信模板申请
//        Route::post('temp', '.SmsTemplate/apply')->name('smsCreate');
//        //公共短信模板列表
//        Route::get('public', '.SmsTemplate/public')->name('smsPublicTemplate');
//        //剩余条数
//        Route::get('number', '.SmsPay/number')->name('smsNumber');
//        //获取支付套餐
//        Route::get('price', '.SmsPay/price')->name('smsPrice');
//        //获取支付码
//        Route::post('pay_code', '.SmsPay/pay')->name('smsPay');
//        //修改密码
//        Route::post('change_password', '.Sms/changePassword')->name('smsChangePassword');
//        //修改簽名
//        Route::post('change_sign', '.Sms/changeSign')->name('smsChangeSign');
//    })->prefix('admin.system.sms');




})->middleware(AllowOriginMiddleware::class)
    ->middleware(AdminTokenMiddleware::class, true)
    ->middleware(AdminAuthMiddleware::class)
    ->middleware(LogMiddleware::class);
