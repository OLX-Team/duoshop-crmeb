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

class StoreRefundProduct extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'refund_product_id';
    }

    public static function tableName(): string
    {
        return 'store_refund_product';
    }

    public function product()
    {
        return $this->hasOne(StoreOrderProduct::class,'order_product_id','order_product_id');
    }

    public function refundOrder()
    {
        return $this->hasOne(StoreRefundOrder::class,'refund_order_id','refund_order_id');
    }
}
