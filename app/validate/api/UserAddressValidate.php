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


namespace app\validate\api;

use think\Validate;

class UserAddressValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'real_name|收货人姓名' => 'require',
        'phone|收货人电话' => 'require|alphaDash|mobile',
        'area|收货信息' => 'require|array|min:3|checkArea',
        'detail|收货人详细地址' => 'require',
    ];

    protected $scene = [
        'take' => ['real_name', 'phone']
    ];

    public function checkArea($list)
    {
        foreach ($list as $item) {
            if (!isset($item['name'], $item['id']))
                return '请选择正确的收货地址';
        }
        return true;
    }
}
