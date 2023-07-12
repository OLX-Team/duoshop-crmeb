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

class GuaranteeTemplateValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'template_name|模板名称' => 'require',
        'template_value|保障服务条款' => 'require|array',
        'status|是否开启' => 'in:0,1',
    ];
}
