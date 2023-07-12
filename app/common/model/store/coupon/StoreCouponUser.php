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
use app\common\model\system\merchant\Merchant;
use app\common\model\user\User;

/**
 * Class StoreCouponUser
 * @package app\common\model\store\coupon
 * @author xaboy
 * @day 2020-05-14
 */
class StoreCouponUser extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'coupon_user_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'store_coupon_user';
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function coupon()
    {
        return $this->hasOne(StoreCoupon::class, 'coupon_id', 'coupon_id');
    }

    public function product()
    {
        return $this->hasMany(StoreCouponProduct::class, 'coupon_id', 'coupon_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }
}
