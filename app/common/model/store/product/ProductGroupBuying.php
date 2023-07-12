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
use app\common\model\system\merchant\Merchant;

class ProductGroupBuying extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tablePk(): string
    {
        return 'group_buying_id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tableName(): string
    {
        return 'store_product_group_buying';
    }


    public function getStopTimeAttr()
    {
        return date('Y-m-d H:i:s',$this->end_time);
    }

    public function groupUser()
    {
        return $this->hasMany(ProductGroupUser::class,'group_buying_id','group_buying_id');
    }

    public function initiator()
    {
        return $this->hasOne(ProductGroupUser::class,'group_buying_id','group_buying_id')->where('is_initiator',1);
    }
    public function productGroup()
    {
        return $this->hasOne(ProductGroup::class,'product_group_id','product_group_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }






    public function searchEndTimeAttr($query,$value)
    {
        $query->where('end_time','<=',$value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchIsDelAttr($query,$value)
    {
        $query->where('is_del',$value);
    }

    public function searchProductGroupIdAttr($query,$value)
    {
        $query->where('product_group_id',$value);
    }

    public function searchGroupBuyingIdAttr($query,$value)
    {
        $query->where('group_buying_id',$value);
    }


}
