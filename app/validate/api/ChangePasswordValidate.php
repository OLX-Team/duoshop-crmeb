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


namespace app\validate\api;


use think\Validate;

class ChangePasswordValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'phone|手机号' => 'require|mobile',
        'pwd|密码' => 'require|min:6',
        'sms_code|短信验证码' => 'require|max:4',
    ];
}
