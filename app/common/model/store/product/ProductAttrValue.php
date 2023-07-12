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

class ProductAttrValue extends BaseModel
{


    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tablePk(): string
    {
        return 'value_id';
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tableName(): string
    {
        return 'store_product_attr_value';
    }

    public function getDetailAttr($value)
    {
        return json_decode($value);
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'product_id','product_id');
    }

    public function getSvipPriceAttr()
    {
        if (isset($this->product->product_type) && $this->product->product_type == 0 && $this->product->show_svip_price && $this->product->svip_price_type == 1) {
            $rate = merchantConfig($this->product->mer_id,'svip_store_rate');
            $svip_store_rate = $rate > 0 ? bcdiv($rate,100,2) : 0;
            return bcmul($this->price, $svip_store_rate,2);
        }
        return $this->getData('svip_price');
    }

    public function getIsSvipPriceAttr()
    {
        if (isset($this->product->product_type) && $this->product->product_type == 0 && $this->product->svip_price_type == 1) {
            $rate = merchantConfig($this->product->mer_id, 'svip_store_rate');
            $svip_store_rate = $rate > 0 ? bcdiv($rate, 100, 2) : 0;
            return bcmul($this->price, $svip_store_rate, 2);
        }
        return '未设置';
    }

    public function getBcExtensionOneAttr()
    {
        if(!intval(systemConfig('extension_status')))  return 0;
        if($this->product->extension_type == 1)  return $this->extension_one;
        return floatval(round(bcmul(systemConfig('extension_one_rate'), isset($this->org_price) ? $this->org_price : $this->price, 3),2));
    }

    public function getBcExtensionTwoAttr()
    {
        if(!intval(systemConfig('extension_status')))  return 0;
        if($this->product->extension_type == 1)  return $this->extension_two;
        return floatval(round(bcmul(systemConfig('extension_two_rate'), isset($this->org_price) ? $this->org_price : $this->price, 3),2));
    }

    public function productSku()
    {
        return $this->hasOne(ProductSku::class, 'unique', 'unique');
    }
    public function productCdkey()
    {
        return $this->hasMany(ProductCdkey::class,'value_id','value_id');
    }

    public function searchUniqueAttr($query,$value)
    {
        $query->where('unique',$value);
    }

    public function searchSkuAttr($query,$value)
    {
        $query->where('sku',$value);
    }

    public function searchProductIdAttr($query,$value)
    {
        $query->where('product_id',$value);
    }


}
