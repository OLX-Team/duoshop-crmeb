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


return [
    //token 有效期
    'token_exp' => 6, //6小时
    //token超时多久可自动续期(后台)
    'token_valid_exp' => 30, //30分钟
    //token超时多久可自动续期(用户)
    'user_token_valid_exp' => 7, //7天
    //登录验证码有效期
    'captcha_exp' => 30, //30分钟
    'admin_prefix' => 'admin',
    'merchant_prefix' => 'merchant',
    'service_prefix' => 'kefu',
    'api_admin_prefix' => 'sys',
    'api_merchant_prefix' => 'mer',
    'api_service_prefix' => 'ser',
    'vic_word_status' => 0,
];
