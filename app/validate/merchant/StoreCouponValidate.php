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

class StoreCouponValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'title|优惠券名称' => 'require|max:32',
        'coupon_price|优惠券面值' => 'require|float|>:0|dime',
        'use_min_price|最低消费金额' => 'require|float',
        'coupon_type|有效期类型' => 'require|in:0,1',
        'coupon_time|有效期限' => 'requireIf:coupon_type,0|integer|>:0',
        'use_start_time|有效期限' => 'requireIf:coupon_type,1|array|>:2',
        'sort|排序' => 'require|integer',
        'status|状态' => 'require|in:0,1',
        'type|优惠券类型' => 'require|in:0,1,10,11,12',
        'product_id|商品' => 'requireIf:type,1|array|>:0',
        'send_type|类型' => 'require|in:0,1,2,3,4,5',
        'full_reduction|满赠金额' => 'requireIf:send_type,1|float|>=:0',
        'is_limited|是否限量' => 'require|in:0,1',
        'is_timeout|是否限时' => 'require|in:0,1',
        'range_date|领取时间' => 'requireIf:is_timeout,1|array|length:2',
        'total_count|发布数量' => 'requireIf:is_limited,1|integer|>:0',
    ];

    protected function dime($value)
    {
        if (!bcadd($value, 0, 1) == $value)
            return '优惠券面值最多1位小数';
        return true;
    }

}
