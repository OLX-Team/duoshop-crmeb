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

class StoreCouponSendValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'coupon_id|优惠券' => 'require|integer',
        'mark|用户类型' => 'array',
        'is_all|用户类型' => 'require|in:0,1',
        'search|用户类型' => 'require|array',
        'uid|用户' => 'array'
    ];
}
