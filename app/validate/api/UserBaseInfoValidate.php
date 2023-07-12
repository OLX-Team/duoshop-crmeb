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

class UserBaseInfoValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'nickname|昵称' => 'require|max:8'
    ];

    protected $message = [
        'nickname.max' => '昵称最多8个字符'
    ];
}
