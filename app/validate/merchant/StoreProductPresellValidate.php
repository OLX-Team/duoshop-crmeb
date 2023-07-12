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

class StoreProductPresellValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        "image|主图" => 'require|max:128',
        "slider_image|轮播图" => 'require',
        "store_name|商品名称" => 'require|max:128',
        "store_info|商品简介" => 'require|max:128',
        "product_id|原商品ID" => 'require',
        "temp_id|运费模板" => 'require',
        "start_time|开始时间" => 'require|checkTime',
        "end_time|结束时间" => "require",
        "presell_type|预售类型" => "require",
        "final_start_time|尾款支付开始时间" => "requireIf:presell_type,2",
        "final_end_time|尾款支付结束时间" => "requireIf:presell_type,2",
        "delivery_type|发货类型" => "require",
        "delivery_day|发货等待天数" => "require",
        "attrValue|商品属性" => "require|Array"
    ];

    protected function checkTime($value,$rule,$data)
    {
        $start_time = strtotime($data['start_time']);
        $end_time = strtotime($data['end_time']);
        if($start_time > $end_time) return '活动开始时间必须小于结束时间';
        if($start_time < time()) return '活动开始时间必须大于当前时间';
        if($data['presell_type'] == 2){
            $final_start_time = strtotime($data['final_start_time']);
            $final_end_time = strtotime($data['final_end_time']);
            if($end_time > $final_start_time) return '尾款支付开始时间必须大于定金结束时间';
            if($final_end_time < $final_start_time) return '尾款支付开始时间必须小于结束时间';
        }
        return true;
    }
}
