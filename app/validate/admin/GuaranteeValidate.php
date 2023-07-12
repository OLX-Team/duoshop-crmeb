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

class GuaranteeValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'guarantee_name|保障服务名称' => 'require',
        'guarantee_info|保障服务简介' => 'require',
        'image|图标' => 'require',
        'status|是否开启' => 'in:0,1',
    ];
}
