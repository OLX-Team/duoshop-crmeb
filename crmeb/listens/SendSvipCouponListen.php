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


namespace crmeb\listens;

use crmeb\interfaces\ListenerInterface;
use crmeb\jobs\CloseSvipCouponJob;
use crmeb\jobs\SendSvipCouponJob;
use crmeb\services\TimerService;
use think\facade\Cache;
use think\facade\Queue;

class SendSvipCouponListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(1000 * 60 * 60 * 23, function () {
            $key = 'send_svip_coupon_status';
            $nuxt = Cache::get($key);
            if (!$nuxt || $nuxt < time()) {
                Queue::later(60,SendSvipCouponJob::class,[]);
                $day = date('Y-m-d',time());

                $nuxt = strtotime(date('Y-m-01', strtotime($day)) . ' +1 month');
                $delay = $nuxt - time();
                Cache::set($key, $nuxt);
                Queue::later($delay,SendSvipCouponJob::class,[]);

                $last = strtotime(date('Y-m-d',$nuxt) . " -1 day");
                $ldelay = (($last - time()) > 0) ?: 10;
                Queue::later($ldelay,CloseSvipCouponJob ::class,[]);
            }
        });
    }
}
