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

use think\Validate;

class StoreProductAdminValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        "store_name|商品名称" => 'require',
        "is_hot|是否热卖" => "in:0,1",
        "is_best|是否精品" => "in:0,1",
        "ficti|已售数量" => "number",
        "status|审核状态" => "in:0,1,-1",
        "refusal|拒绝理由" => "requireIf:status,-1"
    ];
}
