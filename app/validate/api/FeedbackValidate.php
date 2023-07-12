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

class FeedbackValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'type|类型' => 'require',
        'images|图片' => 'array|max:6',
        'realname|姓名' => 'require|>:1',
        'contact|联系方式' => 'require|checkContact'
    ];

    protected function checkContact($val)
    {
        if ($this->regex($val, 'mobile') || $this->filter($val, 'email'))
            return true;
        else
            return '请输入正确的联系方式';
    }
}
