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

namespace app\validate\merchant;

use think\Exception;
use think\File;
use think\Validate;

class StoreDiscountsValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        //"image|套餐图片" => 'require|max:128',
        "title|套餐标题" => 'require|max:128',
        "type|套餐类型" => 'require|in:0,1',
        "is_limit|是否限够" => 'require',
        "limit_num|限购数量" => 'require|max:4',
        "is_time|是否限时" => "in:0,1",
        "sort|排序" => "require",
        "free_shipping|是否包邮" => "require",
        'status|发货方式' => 'require',
        'products|发货方式' => 'require',
    ];
}
