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

class PriceRuleValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'rule_name|名称' => 'require|max:32',
        'cate_id|分类' => 'array',
        'sort|排序' => 'require|integer',
        'is_show|是否显示' => 'require|in:0,1',
        'content|价格说明详情' => 'require',
    ];

}
