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

class MerchantTypeValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'type_name|店铺类型名称' => 'require|max:5',
        'type_info|店铺类型要求' => 'max:256',
        'is_margin|是否有保证金'  => 'require|in:0,1',
        'auth|权限'  => 'require|array|min:1',
        'margin|保证金(¥)'       => 'requireIf:is_margin,1',
        'description|其他说明'       => 'max:256',
    ];
}
