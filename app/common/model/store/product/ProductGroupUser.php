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
use app\common\model\store\order\StoreOrder;

class ProductGroupUser extends BaseModel
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


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tableName(): string
    {
        return 'store_product_group_user';
    }

    public function orderInfo()
    {
        return $this->hasOne(StoreOrder::class,'order_id','order_id');
    }

    public function groupBuying()
    {
        return $this->hasOne(ProductGroupBuying::class,'group_buying_id','group_buying_id');
    }

    public function productGroup()
    {
        return $this->hasOne(ProductGroup::class,'product_group_id','product_group_id');
    }

//    public function getAvatarAttr($value)
//    {
//        return $value ? $value : '/static/f.png';
//    }

    public function searchProductGroupIdAttr($query,$value)
    {
        $query->where('product_group_id',$value);
    }

    public function searchGroupBuyingIdAttr($query,$value)
    {
        $query->where('group_buying_id',$value);
    }

    public function searchUidAttr($query,$value)
    {
        $query->where('uid',$value);
    }

    public function searchIsDelAttr($query,$value)
    {
        $query->where('is_del',$value);
    }
}
