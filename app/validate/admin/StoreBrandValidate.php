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

class StoreBrandValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'brand_category_id|上级分类' => 'require|integer',
        'brand_name|名称' => 'require|max:32',
        'is_show|状态' => 'require|in:0,1',
        'sort|排序' => 'require|integer',
        'pic|图标'   => 'max:128'
    ];
}
