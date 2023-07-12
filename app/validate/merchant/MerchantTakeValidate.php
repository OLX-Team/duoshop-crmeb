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

class MerchantTakeValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'mer_take_name|自提点名称' => 'require',
        'mer_take_phone|自提点手机号' => 'require|mobile',
        'mer_take_address|自提点地址' => 'require',
        'mer_take_location|店铺经纬度' => 'require|array|length:2',
        'mer_take_day|自提点营业日期' => 'array|max:7',
        'mer_take_time|自提点营业时间' => 'array|length:2',
    ];
}
