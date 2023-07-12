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


namespace crmeb\jobs;


use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class CloseSvipCouponJob implements JobInterface
{
    public function fire($job, $type)
    {
        $meka = app()->make(StoreCouponRepository::class);
        try {
            $couponIds = $meka->validCouponQuery(null,StoreCouponRepository::GET_COUPON_TYPE_SVIP)->column('coupon_id');
            app()->make(StoreCouponUserRepository::class)->getSearch([])->whereIn('coupon_id',$couponIds)->update(['status' => 2]);
        } catch (\Exception $e) {
            Log::INFO('付费会员优惠券过期操作失败：'.implode(',',$couponIds));
        };
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
