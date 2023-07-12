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

class ProductAssistSku extends BaseModel
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
        return 'store_product_assist_sku';
    }

    public function sku()
    {
        return $this->hasOne(ProductAttrValue::class,'unique','unique');
    }


    public function searchUniqueAttr($query,$value)
    {
        $query->where('unique',$value);
    }
    public function searchProductIdAttr($query,$value)
    {
        $query->where('product_id',$value);
    }
    public function searchProductAssistIdAttr($query,$value)
    {
        $query->where('product_assist_id',$value);
    }
}
