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

class StoreCartValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'product_id|商品ID' => 'require',
        'product_attr_unique|SKU' => 'require',
        'cart_num|购买数量' => 'require|integer|>:0',
    ];
}
