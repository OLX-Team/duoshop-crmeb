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


namespace app\common\model\store\product;

use app\common\model\BaseModel;
use app\common\model\user\User;

class ProductTake extends BaseModel
{


    public static function tablePk(): string
    {
        return 'product_take_id';
    }


    public static function tableName(): string
    {
        return 'store_product_take';
    }



    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }

    public function sku()
    {
        return $this->hasOne(ProductAttrValue::class,'unique','unique');
    }
    public function user()
    {
        return $this->hasOne(User::class,'uid','uid');
    }


    public function searchUniqueAttr($query,$value)
    {
        $query->where('unique',$value);
    }

    public function searchUidAttr($query,$value)
    {
        $query->where('uid',$value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchProductIdAttr($query,$value)
    {
        $value = is_array($value) ? $value : [$value];
        $query->whereIn('product_id',$value);
    }

    public function searchTypeAttr($query,$value)
    {
        $query->where('type',$value);
    }
}
