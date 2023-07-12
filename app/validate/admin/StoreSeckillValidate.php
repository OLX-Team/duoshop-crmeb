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

class StoreSeckillValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'start_time|开始时间' => 'require|number|between:0,24',
        'end_time|结束时间'   => 'require|number|between:0,24|>:start_time',
        'status|状态'        => 'require|in:0,1',
    ];
}
