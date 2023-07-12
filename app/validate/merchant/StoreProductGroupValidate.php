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

class StoreProductGroupValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        "image|主图" => 'require|max:128',
        "slider_image|轮播图" => 'require',
        "store_name|商品名称" => 'require|max:128',
        "store_info|商品简介" => 'require',
        "product_id|原商品ID" => 'require',
        "temp_id|运费模板" => 'require',
        "start_time|开始时间" => 'require',
        "end_time|结束时间" => "require",
        "buying_count_num|开团总人数" => "require|>=:buying_num",
        "attrValue|商品属性" => "require|Array|checkAttrValue",
        "time|开团时长" => "require"
    ];

    protected function checkAttrValue($value,$rule,$data)
    {
        foreach ($value as $v){
            if($v['stock'] > $v['old_stock']){
                return '限量不可大于库存';
            }
        }
        return true;
    }
}
