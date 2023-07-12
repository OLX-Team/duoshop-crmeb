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
use app\common\repositories\store\order\StoreOrderRepository;

class ProductGroupSku extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tablePk(): string
    {
        return '';
    }

    public function sku()
    {
        return $this->hasOne(ProductAttrValue::class,'unique','unique');
    }

    public function getSalesAttr()
    {
        $make = app()->make(StoreOrderRepository::class);
        $where = [
            'product_sku' => $this->unique,
            'product_type' => 4,
            'exsits_id' => $this->product_group_id,
        ];
        $count = $make->getTattendCount($where,null)->sum('product_num');

        return $count;
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tableName(): string
    {
        return 'store_product_group_sku';
    }

    public function searchProductGroupIdAttr($query,$value)
    {
        $query->where('product_group_id',$value);
    }

    public function searchuniqueAttr($query,$value)
    {
        $query->where('unique',$value);
    }
}
