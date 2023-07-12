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

class StoreDiscounts extends BaseModel
{

    public static function tablePk(): string
    {
        return 'discount_id';
    }

    public static function tableName(): string
    {
        return 'store_discounts';
    }

    public function discountsProduct()
    {
        return $this->hasMany(StoreDiscountProduct::class, 'discount_id', 'discount_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }


    public function getTimeAttr()
    {
        if ($this->is_time) {
            return [date('Y-m-d H:i:s',$this->start_time),date('Y-m-d H:i:s', $this->stop_time)];
        }
        return [];
    }



    public function searchTitleAttr($query, $value)
    {
        $query->whereLike('title', "%{$value}%");
    }

    public function searchMerIdAttr($query, $value)
    {
        $query->where('mer_id', $value);
    }

    public function searchStoreNameAttr($query, $value)
    {
        $id = StoreDiscountProduct::whereLike('store_name', "%{$value}%")->column('discount_id');
        $query->whereIn('discount_id', $id);
    }

    public function searchDiscountIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('discount_id', $value);
        } else {
            $query->where('discount_id', $value);
        }

    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    public function searchIsShowAttr($query, $value)
    {
        $query->where('is_show', $value);
    }

    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value);
    }

    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }


    public function searchEndTimeAttr($query, $value)
    {
        $query->where(function ($query) {
            $query->where('is_time', 0)->whereOr(function ($query) {
                $query->where('is_time', 1)->where('start_time', '<', time())->where('stop_time', '>', time());
            });
        });

    }
}
