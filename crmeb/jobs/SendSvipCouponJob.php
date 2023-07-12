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
use app\common\repositories\system\CacheRepository;
use app\common\repositories\user\UserRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;

class SendSvipCouponJob implements JobInterface
{
    public function fire($job, $type)
    {
        $moth = date('Y-m-d',time());
        $meka = app()->make(StoreCouponRepository::class);
        try {
            $couponIds = $meka->sendSvipCoupon();
        } catch (\Exception $e) {
            Log::INFO('发送付费会员优惠券失败：'.$moth);
        };
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
