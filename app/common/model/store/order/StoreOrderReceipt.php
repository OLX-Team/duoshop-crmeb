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

class StoreOrderReceipt extends BaseModel
{
    public static function tablePk(): ?string
    {
        return 'order_receipt_id';
    }

    public static function tableName(): string
    {
        return 'store_order_receipt';
    }

    public function getReceiptInfoAttr($value)
    {
        return json_decode($value);
    }

    public function getDeliveryInfoAttr($value)
    {
        return json_decode($value);
    }

    public function storeOrder()
    {
        return $this->hasOne(StoreOrder::class,'order_id','order_id');
    }

    public function user()
    {
        return $this->hasOne(User::class,'uid','uid');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }

    public function searchOrderReceiptIdsAttr($query,$value)
    {
        $query->whereIn('order_receipt_id',$value);
    }
    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }
    public function searchOrderReceiptIdAttr($query,$value)
    {
        $query->where('order_receipt_id',$value);
    }
}
