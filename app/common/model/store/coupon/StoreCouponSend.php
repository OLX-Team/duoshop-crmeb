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


namespace app\common\model\store\coupon;


use app\common\model\BaseModel;
use app\common\repositories\store\coupon\StoreCouponUserRepository;

class StoreCouponSend extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'coupon_send_id';
    }

    public static function tableName(): string
    {
        return 'store_coupon_send';
    }

    public function setMarkAttr(array $val)
    {
        return json_encode($val);
    }

    public function getMarkAttr($val)
    {
        return json_decode($val, true) ?: [];
    }

    public function getUseCountAttr()
    {
        return app()->make(StoreCouponUserRepository::class)->sendNum($this->coupon_id, $this->coupon_send_id, 1);
    }

    public function getUsedNumAttr()
    {
        return app()->make(StoreCouponUserRepository::class)->usedNum($this->coupon_id);
    }

    public function getSendNumAttr()
    {
        return app()->make(StoreCouponUserRepository::class)->sendNum($this->coupon_id);
    }

    public function coupon()
    {
        return $this->hasOne(StoreCoupon::class, 'coupon_id', 'coupon_id');
    }
}
