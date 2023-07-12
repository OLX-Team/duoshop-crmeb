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
use app\common\middleware\AllowOriginMiddleware;
//无需登录接口
Route::group(function () {
    //短信支付成功回调
    Route::any('sms/notice', 'admin.system.sms.SmsPay/notice');
    //验证码
    Route::get('captcha', 'admin.system.admin.Login/getCaptcha');
    //登录
    Route::post('login', 'admin.system.admin.Login/login');
    Route::post('ajstatus', 'admin.system.admin.Login/ajCaptchaStatus');
    //登录配置
    Route::get('login_config', 'admin.Common/loginConfig');
    //滑块验证码
    Route::get('ajcaptcha', 'api.Auth/ajcaptcha');
    Route::post('ajcheck', 'api.Auth/ajcheck');
})->middleware(AllowOriginMiddleware::class)->option([
    '_auth' => false,
]);
