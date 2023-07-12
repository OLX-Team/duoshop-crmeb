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


namespace app\common\model\store\order;


use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\repositories\store\coupon\StoreCouponRepository;

class StoreGroupOrder extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'group_order_id';
    }

    public static function tableName(): string
    {
        return 'store_group_order';
    }

    public function orderList()
    {
        return $this->hasMany(StoreOrder::class, 'group_order_id', 'group_order_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function getGiveCouponAttr()
    {
        if (count($this->give_coupon_ids))
            return app()->make(StoreCouponRepository::class)->getGiveCoupon($this->give_coupon_ids);
        return [];
    }

    public function getCancelTimeAttr()
    {
        $timer = ((int)systemConfig('auto_close_order_timer')) ?: 15;
        return date('m-d H:i', strtotime("+ $timer minutes", strtotime($this->create_time)));
    }

    public function getCancelUnixAttr()
    {
        $timer = ((int)systemConfig('auto_close_order_timer')) ?: 15;
        return strtotime("+ $timer minutes", strtotime($this->create_time));
    }

    public function getGiveCouponIdsAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function setGiveCouponIdsAttr($value)
    {
        return $value ? implode(',', $value) : '';
    }

    public function getCombinePayParams()
    {
        $params = [
            'order_sn' => $this->group_order_sn,
            'sub_orders' => [],
            'attach' => 'order',
            'body' => '订单支付',
        ];
        foreach ($this->orderList as $order) {
            if ($order->pay_price > 0) {
                $subOrder = [
                    'pay_price' => $order->pay_price,
                    'order_sn' => $order->order_sn,
                    'sub_mchid' => $order->merchant->sub_mchid,
                ];
                $params['sub_orders'][] = $subOrder;
            }
        }
        return $params;
    }

    public function getPayParams($return_url = '')
    {
        $params = [
            'order_sn' => $this->group_order_sn,
            'pay_price' => $this->pay_price,
            'attach' => 'order',
            'body' => '订单支付'
        ];
        if ($return_url) {
            $params['return_url'] = $return_url;
        }
        return $params;
    }
}
