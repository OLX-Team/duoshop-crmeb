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
use app\common\model\user\User;

class StoreCouponIssueUser extends BaseModel
{

    public static function tablePk(): ?string
    {
        return null;
    }

    public static function tableName(): string
    {
        return 'store_coupon_issue_user';
    }

    public function coupon()
    {
        return $this->hasOne(StoreCoupon::class, 'coupon_id', 'coupon_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }
}
