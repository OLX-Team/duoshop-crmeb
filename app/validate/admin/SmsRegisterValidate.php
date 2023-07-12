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


namespace app\validate\admin;


use think\Validate;

class SmsRegisterValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'account|账号' => 'require',
        'password|密码' => 'require',
        'phone|手机号' => 'require|isPhone',
        'code|验证码' => 'require',
        'url|域名' => 'require|url',
        'sign|短信签名' => 'require|max:8'
    ];

    protected function isPhone($val)
    {
        if (!preg_match('/^1[3456789]{1}\d{9}$/', $val))
            return '请输入正确的手机号';
        else
            return true;
    }

    public function isLogin()
    {
        unset($this->rule['phone|手机号'], $this->rule['code|验证码'], $this->rule['url|域名'], $this->rule['sign|短信签名']);
        return $this;
    }
}
