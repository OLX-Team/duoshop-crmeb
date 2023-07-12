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

class StoreProductValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        "image|主图" => 'require|max:128',
        "store_name|商品名称" => 'require|max:128',
        "cate_id|平台分类" => 'require',
        "mer_cate_id|商户分类" => 'array',
        "unit_name|单位名" => 'require|max:4',
        "spec_type" => "in:0,1",
        "is_show｜是否上架" => "in:0,1",
        "extension_type|分销类型" => "in:0,1",
        "attr|商品规格" => "requireIf:spec_type,1|Array|checkUnique",
        "attrValue|商品属性" => "require|array|productAttrValue",
        'type|商品类型' => 'require|in:0,1,2',
        'delivery_way|发货方式' => 'requireIf:is_ficti,0|require',
        'once_min_count|最小限购' => 'min:0',
        'pay_limit|是否限购' => 'require|in:0,1,2|payLimit',
    ];

    protected function payLimit($value,$rule,$data)
    {
        if ($value && ($data['once_max_count'] < $data['once_min_count']))
           return '限购数量不能小于最少购买件数';
        return true;
    }

    protected function productAttrValue($value,$rule,$data)
    {
        $arr = [];
        try{
            foreach ($value as $v){
                $sku = '';
                if(isset($v['detail']) && is_array($v['detail'])){
                    sort($v['detail'],SORT_STRING);
                    $sku = implode(',',$v['detail']);
                }
                if(in_array($sku,$arr)) return '商品SKU重复';
                $arr[] = $sku;
                if(isset($data['extension_type']) && $data['extension_type'] && systemConfig('extension_status')){
                    if(!isset($v['extension_one']) || !isset($v['extension_two'])) return '佣金金额必须填写';
                    if(($v['extension_one'] < 0) || ($v['extension_two'] < 0))
                        return '佣金金额不可存在负数';
                    if($v['price'] < bcadd($v['extension_one'],$v['extension_two'],2))
                        return '自定义佣金总金额不能大于商品售价';
                }
                if ($data['product_type'] == 20 && !$v['ot_price']) {
                    return '积分商品兑换积分必须大于0.'.$data['product_type'] ;
                }
            }
        } catch (\Exception $exception) {
            return '商品属性格式错误';
        }
        return true;
    }

    protected function checkUnique($value)
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
