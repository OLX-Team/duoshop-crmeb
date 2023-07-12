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
use app\common\model\community\Community;
use app\common\model\store\product\ProductGroupUser;
use app\common\model\store\service\StoreService;
use app\common\model\store\shipping\Express;
use app\common\model\system\merchant\Merchant;
use app\common\model\user\User;
use app\common\repositories\store\MerchantTakeRepository;

class StoreOrder extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'order_id';
    }

    public static function tableName(): string
    {
        return 'store_order';
    }

    public function orderProduct()
    {
        return $this->hasMany(StoreOrderProduct::class, 'order_id', 'order_id');
    }

    public function refundProduct()
    {
        return $this->orderProduct()->where('refund_num', '>', 0);
    }

    public function refundOrder()
    {
        return $this->hasMany(StoreRefundOrder::class,'order_id','order_id');
    }

    public function orderStatus()
    {
        return $this->hasMany(StoreOrderStatus::class,'order_id','order_id')->order('change_time DESC');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }
    public function receipt()
    {
        return $this->hasOne(StoreOrderReceipt::class, 'order_id', 'order_id');
    }

    public function spread()
    {
        return $this->hasOne(User::class, 'uid', 'spread_uid');
    }

    public function TopSpread()
    {
        return $this->hasOne(User::class, 'uid', 'top_uid');
    }

    public function groupOrder()
    {
        return $this->hasOne(StoreGroupOrder::class, 'group_order_id', 'group_order_id');
    }

    public function verifyService()
    {
        return $this->hasOne(StoreService::class, 'service_id', 'verify_service_id');
    }

    public function getTakeAttr()
    {
        return app()->make(MerchantTakeRepository::class)->get($this->mer_id);
    }

    public function searchDataAttr($query, $value)
    {
        return getModelTime($query, $value);
    }

    public function presellOrder()
    {
        return $this->hasOne(PresellOrder::class, 'order_id', 'order_id');
    }

    public function finalOrder()
    {
        return $this->hasOne(PresellOrder::class,'order_id','order_id');
    }

    public function groupUser()
    {
        return $this->hasOne(ProductGroupUser::class,'order_id','order_id');
    }

    public function profitsharing()
    {
        return $this->hasMany(StoreOrderProfitsharing::class, 'order_id', 'order_id');
    }

    public function firstProfitsharing()
    {
        return $this->hasOne(StoreOrderProfitsharing::class, 'order_id', 'order_id')->where('type', 'order');
    }

    public function presellProfitsharing()
    {
        return $this->hasOne(StoreOrderProfitsharing::class, 'order_id', 'order_id')->where('type', 'presell');
    }

    // 核销订单的自订单列表
    public function takeOrderList()
    {
        return $this->hasMany(self::class,'main_id','order_id')->order('verify_time DESC');
    }

    public function searchMerIdAttr($query, $value)
    {
        return $query->where('mer_id', $value);
    }

    public function getRefundStatusAttr()
    {
        $day = (int)systemConfig('sys_refund_timer') ?: 15;
        return ($this->verify_time ? strtotime($this->verify_time) > strtotime('-' . $day . ' day') : true);
    }

    public function getCancelTimeAttr()
    {
        if (!$this->apid) {
            $timer = ((int)systemConfig('auto_close_order_timer')) ?: 15;
            return date('m-d H:i', strtotime("+ $timer minutes", strtotime($this->groupOrder->create_time)));
        }
        return null;
    }

    public function getOpenReceiptAttr()
    {
        return merchantConfig($this->mer_id,'mer_open_receipt') ?: 0;
    }

    public function getOrderExtendAttr($val)
    {
        return $val ? json_decode($val, true) : null;
    }

    public function getRefundExtensionOneAttr()
    {
        if ( $this->refundOrder ){
            return $this->refundOrder()->where('status',3)->sum('extension_one');
        }
        return 0;
    }

    public function getRefundExtensionTwoAttr()
    {
       if ( $this->refundOrder ){
           return $this->refundOrder()->where('status',3)->sum('extension_two');
       }
       return 0;
    }

    public function community()
    {
        return $this->hasOne(Community::class, 'order_id', 'order_id')->bind(['community_id']);
    }

    public function getRefundPriceAttr()
    {
       return StoreRefundOrder::where('order_id',$this->order_id)->where('status',3)->sum('refund_price');
    }
}
