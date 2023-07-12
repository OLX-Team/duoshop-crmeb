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
use app\common\model\system\merchant\Merchant;
use crmeb\services\DeliverySevices;

class DeliveryStation extends BaseModel
{

    public static function tablePk(): string
    {
        return 'station_id';
    }

    public static function tableName(): string
    {
        return 'delivery_station';
    }

    public function getBusinessAttr($value, $data)
    {
        $res = DeliverySevices::create($data['type'])->getBusiness();
        foreach ($res as $v) {
            if ($value == $v['key']) {
                return $v;
            }
        }
        return $value;
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id','mer_id');
    }

    public function searchStationNameAttr($query,$value)
    {
        $query->whereLike('station_name',"%{$value}%");
    }

    public function searchKeywordAttr($query,$value)
    {
        $query->whereLike('station_name|contact_name|phone',"%{$value}%");
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchStationIdAttr($query,$value)
    {
        $query->where('station_id',$value);
    }

    public function searchIsDelAttr($query,$value)
    {
        $query->where('is_del',$value);
    }

    public function searchTypeAttr($query,$value)
    {
        $query->where('type',$value);
    }
    public function searchDateAttr($query,$value)
    {
        getModelTime($query, $value, 'create_time');
    }
}
