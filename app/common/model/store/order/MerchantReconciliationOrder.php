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

class MerchantReconciliationOrder extends BaseModel
{
    public static function tablePk(): ?string
    {
        return '';
    }

    public static function tableName(): string
    {
        return 'merchant_reconciliation_order';
    }

    public function Order()
    {
        return $this->hasOne(StoreOrder::class,'order_id','order_id');
    }

    public function refund()
    {
        return $this->hasOne(StoreRefundOrder::class,'refund_order_id','order_id');
    }
}
