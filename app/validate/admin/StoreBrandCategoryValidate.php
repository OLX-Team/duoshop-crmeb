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

class StoreBrandCategoryValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'pid|上级分类' => 'require|integer',
        'cate_name|分类名称' => 'require|max:12',
        'is_show|状态' => 'require|in:0,1',
        'sort|排序' => 'require|integer'
    ];
}
