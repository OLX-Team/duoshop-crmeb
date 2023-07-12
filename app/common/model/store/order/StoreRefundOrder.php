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

class StoreRefundOrder extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'refund_order_id';
    }

    public static function tableName(): string
    {
        return 'store_refund_order';
    }

    public function getPicsAttr($val)
    {
        return $val ? explode(',', $val) : [];
    }

    public function setPicsAttr($val)
    {
        return $val ? implode(',', $val) : '';
    }

    public function refundProduct()
    {
        return $this->hasMany(StoreRefundProduct::class, 'refund_order_id', 'refund_order_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function order()
    {
        return $this->hasOne(StoreOrder::class, 'order_id', 'order_id');
    }

    public function searchDataAttr($query, $value)
    {
        return getModelTime($query, $value);
    }

    public function getAutoRefundTimeAttr()
    {
        $merAgree = systemConfig('mer_refund_order_agree') ?: 7;
        return strtotime('+' . $merAgree . ' day', strtotime($this->status_time));
    }

    public function getCombineRefundParams()
    {
        return [
            'sub_mchid' => $this->merchant->sub_mchid,
            'order_sn' => $this->order->order_sn,
            'refund_order_sn' => $this->refund_order_sn,
            'refund_price' => $this->refund_price,
            'pay_price' => $this->order->pay_price,
            'refund_message' => $this->refund_message,
            'open_id' => $this->user->wechat->routine_openid ?? null,
            'transaction_id' => $this->order->transaction_id,
        ];
    }
}
