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

class PayGiveCouponJob implements JobInterface
{
    public function fire($job, $data)
    {
        $storeCouponRepository = app()->make(StoreCouponRepository::class);
        $coupons = $storeCouponRepository->getGiveCoupon($data['ids']);
        foreach ($coupons as $coupon) {
            if ($coupon->is_limited && 0 == $coupon->remain_count)
                continue;
            try {
                $storeCouponRepository->sendCoupon($coupon, $data['uid'], StoreCouponUserRepository::SEND_TYPE_BUY);
            } catch (\Exception $e) {
                Log::info('自动发放买赠优惠券:' . $e->getMessage());
            }
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
