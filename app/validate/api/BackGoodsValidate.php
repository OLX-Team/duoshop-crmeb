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

class BackGoodsValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'delivery_type|快递公司' => 'require',
        'delivery_id|快递单号' => 'require',
        'delivery_phone|联系电话' => 'require|mobile',
        'delivery_mark|备注' => 'max:128',
        'delivery_pics|凭证' => 'array|max:9',
    ];
}
