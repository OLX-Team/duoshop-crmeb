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
use app\common\repositories\user\UserRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class SendNewPeopleCouponJob implements JobInterface
{

    public function fire($job, $uid)
    {
        if (!app()->make(UserRepository::class)->exists($uid))
            return $job->delete();

        $storeCouponRepository = app()->make(StoreCouponRepository::class);
        $newPeopleCoupon = $storeCouponRepository->newPeopleCoupon();
        foreach ($newPeopleCoupon as $coupon) {
            if ($coupon->is_limited && 0 == $coupon->remain_count)
                continue;
            try {
                $storeCouponRepository->sendCoupon($coupon, $uid, StoreCouponUserRepository::SEND_TYPE_NEW);
            } catch (\Exception $e) {
                Log::info('自定发放优惠券:' . $e->getMessage());
            }
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
