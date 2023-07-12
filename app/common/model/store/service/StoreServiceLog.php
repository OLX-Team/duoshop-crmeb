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


namespace app\common\model\store\service;


use app\common\model\BaseModel;
use app\common\model\store\order\StoreOrder;
use app\common\model\store\order\StoreRefundOrder;
use app\common\model\store\product\Product;
use app\common\model\store\product\ProductGroup;
use app\common\model\store\product\ProductPresell;
use app\common\model\system\merchant\Merchant;
use app\common\model\user\User;

class StoreServiceLog extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'service_log_id';
    }

    public static function tableName(): string
    {
        return 'store_service_log';
    }

    public function orderInfo()
    {
        return $this->hasOne(StoreOrder::class, 'order_id', 'msn')->with('orderProduct');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'msn');
    }

    public function presell()
    {
        return $this->hasOne(ProductPresell::class, 'product_presell_id', 'msn')->append(['product']);
    }

    public function productGroup()
    {
        return $this->hasOne(ProductGroup::class, 'product_group_id', 'msn')->append(['product']);
    }

    public function refundOrder()
    {
        return $this->hasOne(StoreRefundOrder::class, 'refund_order_id', 'msn')->with('refundProduct.product');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field('uid,avatar,nickname');
    }

    public function service()
    {
        return $this->hasOne(StoreService::class, 'service_id', 'service_id')->field('uid,service_id,avatar,nickname');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function getSendTimeAttr()
    {
        return strtotime($this->create_time);
    }

    public function getSendDateAttr()
    {
        return date('H:i',strtotime($this->create_time));
    }
}
