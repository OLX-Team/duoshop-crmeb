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


namespace app\common\model\delivery;

use app\common\model\BaseModel;
use app\common\model\store\order\StoreOrder;
use app\common\model\store\order\StoreOrderStatus;
use app\common\model\system\merchant\Merchant;
use crmeb\services\DeliverySevices;

class DeliveryOrder extends BaseModel
{

    public static function tablePk(): string
    {
        return 'delivery_order_id';
    }

    public static function tableName(): string
    {
        return 'delivery_order';
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id','mer_id');
    }

    public function station()
    {
        return $this->hasOne(DeliveryStation::class, 'station_id','station_id');
    }

    public function storeOrder()
    {
        return $this->hasOne(StoreOrder::class, 'order_id','order_id');
    }

    public function storeOrderStatus()
    {
        return $this->hasMany(StoreOrderStatus::class, 'order_id','order_id')->order('change_time DESC');
    }

    public function getDistanceAttr($value)
    {
        if ($value >= 1000) {
            return round(($value / 1000),2) . ' km';
        } else {
            return $value . 'm';
        }
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchKeywordAttr($query,$value)
    {
        $query->whereLike('order_sn|from_address',"%{$value}%");
    }

    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchStationIdAttr($query,$value)
    {
        $query->where('station_id',$value);
    }

    public function searchStationTypeAttr($query,$value)
    {
        $query->where('station_type',$value);
    }

    public function searchOrderIdAttr($query,$value)
    {
        $query->where('order_id',$value);
    }
    public function searchSnAttr($query,$value)
    {
        $query->where('order_sn',$value);
    }
    public function searchDateAttr($query,$value)
    {
        getModelTime($query, $value);
    }
    public function searchOrderSnAttr($query,$value)
    {
        $ids = StoreOrder::whereLike('order_sn',"%{$value}%")->column('order_id');
        $query->whereIn('order_id', $ids);
    }
}
