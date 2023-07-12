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

class GroupValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'group_name|数据组名称' => 'require|max:32',
        'group_info|数据组说明' => 'max:128',
        'group_key|数据组key' => 'require|max:32',
        'fields|数据组字段' => 'require|array',
        'sort|排序' => 'require|integer',
        'user_type|后台类型' => 'require|in:0,1'
    ];
}
