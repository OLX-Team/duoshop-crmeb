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

class IntegralConfigValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'integral_status|积分开关' => 'require|in:0,1',
        'integral_clear_time|积分清除时间' => 'require|integer|>=:0',
        'integral_order_rate|下单赠送积分比例' => 'require|float|>=:0',
        'integral_freeze|下单赠送积分冻结期' => 'require|integer|>=:0',
        'integral_user_give|邀请好友赠送积分' => 'require|integer|>=:0',
        'integral_money|积分抵用金额' => 'require|float|>=:0',
    ];

}
