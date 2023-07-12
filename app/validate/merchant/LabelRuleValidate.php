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


namespace app\validate\merchant;


use think\Validate;

class LabelRuleValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'type|规则类型' => 'require|in:0,1',
        'min|最小值' => 'require|float|>=:0',
        'max|最大值' => 'require|float|>=:min',
        'label_name|标签名' => 'require|length:2,10'
    ];

    protected $message = [
        'max.egt' => '最大值必须大于等于最小值',
    ];
}
