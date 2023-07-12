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

class ProductPresellSku extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tablePk(): string
    {
        return '';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tableName(): string
    {
        return 'store_product_presell_sku';
    }

    public function sku()
    {
        return $this->hasOne(ProductAttrValue::class,'unique','unique');
    }

    public function presell()
    {
        return $this->hasOne(ProductPresell::class,'product_presell_id','product_presell_id');
    }

    public function searchUniqueAttr($query,$value)
    {
        $query->where('unique',$value);
    }
    public function searchProductIdAttr($query,$value)
    {
        $query->where('product_id',$value);
    }
    public function searchProductPresellIdAttr($query,$value)
    {
        $query->where('product_presell_id',$value);
    }

    public function getBcExtensionOneAttr()
    {
        if (!intval(systemConfig('extension_status'))) return 0;
        if ($this->sku->extension_one > 0) return $this->sku->extension_one;
        return floatval(round(bcmul(systemConfig('extension_one_rate'), $this->presell_price, 3), 2));
    }

    public function getBcExtensionTwoAttr()
    {
        if (!intval(systemConfig('extension_status'))) return 0;
        if ($this->sku->extension_two > 0) return $this->sku->extension_two;
        return floatval(round(bcmul(systemConfig('extension_two_rate'), $this->presell_price, 3), 2));
    }
}
