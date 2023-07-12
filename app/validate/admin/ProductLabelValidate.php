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

class ProductLabelValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'label_name|标签名称' => 'require|max:6',
        'info|说明'   => 'max:32',
        'sort|排序'   => 'integer|min:0'
    ];
}
