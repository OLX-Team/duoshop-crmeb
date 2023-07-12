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

class StoreActivityValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        "activity_name|活动名称" => 'require',
        "start_time|开始时间" => "require",
        "end_time|结束时间" => "require|checkEndTime",
    ];

    protected function checkEndTime($value)
    {
        if (strtotime($value) <= time()) {
            return '结束时间必须大于当前时间';
        }
        return true;
    }
}
