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

use think\File;
use think\Validate;

class StoreSeckillProductValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        "image|主图" => 'require|max:128',
        "slider_image|轮播图" => 'require',
        "store_name|商品名称" => 'require|max:128',
        "brand_id|品牌ID" => 'require',
        "cate_id|平台分类" => 'require',
        "mer_cate_id|商户分类" => 'array',
        "unit_name|单位名" => 'require|max:4',
        "spec_type" => "in:0,1",
        "is_show|是否上架" => "in:0,1",
        "start_day|开始日期" => "require",
        "end_day|结束日期" => "require",
        "start_time|开始时间" => "require",
        "end_time|结束时间" => "require",
        "old_product_id|原商品ID" => 'require',
        "once_pay_count|单场限购" => 'require',
        "all_pay_count|限购总数" => 'require',
        "attr|商品规格" => "requireIf:spec_type,1|Array|checkUnique",
        "attrValue|商品属性" => "Array|productAttrValue"
    ];

    protected function productAttrValue($value,$rule,$data)
    {
        $arr = [];
        foreach ($value as $v){
            if ($v['stock'] > $v['old_stock']) return '限量不能大于库存';
            $sku = '';
            if(isset($v['detail']) && is_array($v['detail'])){
                sort($v['detail'],SORT_STRING);
                $sku = implode(',',$v['detail']);
            }
            if($v['stock'] < 1) return '限量不能小于1';
            if(in_array($sku,$arr)) return '商品SKU重复';
            $arr[] = $sku;
        }
        return true;
    }

    public function checkUnique($value)
    {
        $arr = [];
       foreach ($value as $item){
           if(in_array($item['value'],$arr)) return '规格重复';
           $arr[] = $item['value'];
           if (count($item['detail']) != count(array_unique($item['detail']))) return '属性重复';
       }
       return true;
    }
}
