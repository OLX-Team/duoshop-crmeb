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
use app\common\model\system\merchant\Merchant;
use app\common\model\user\User;
use app\common\repositories\store\order\PresellOrderRepository;

class PresellOrder extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'presell_order_id';
    }

    public static function tableName(): string
    {
        return 'presell_order';
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function order()
    {
        return $this->hasOne(StoreOrder::class, 'order_id', 'order_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }


    public function searchOrderIdAttr($query, $value)
    {
        $query->where('order_id', $value);
    }

    public function getActiveStatusAttr()
    {
        $status = 1;
        $now = time();
        if (strtotime($this->final_start_time) > $now) $status = 0;
        else if (strtotime($this->final_end_time) < $now) {
            if ($this->status && $this->presell_order_id)
                app()->make(PresellOrderRepository::class)->cancel($this->presell_order_id);
            $status = 2;
        }
        return $status;
    }

    public function getCombinePayParams()
    {
        return [
            'order_sn' => $this->presell_order_sn,
            'sub_orders' => [
                [
                    'pay_price' => $this->pay_price,
                    'order_sn' => $this->presell_order_sn,
                    'sub_mchid' => $this->merchant->sub_mchid,
                ]
            ],
            'attach' => 'presell',
            'body' => '尾款支付',
        ];
    }

    public function getPayParams($return_url = '')
    {
        $params = [
            'order_sn' => $this->presell_order_sn,
            'pay_price' => $this->pay_price,
            'attach' => 'presell',
            'body' => '尾款支付'
        ];
        if ($return_url) {
            $params['return_url'] = $return_url;
        }
        return $params;
    }
}
