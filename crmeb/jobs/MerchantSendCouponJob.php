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
use app\common\repositories\store\coupon\StoreCouponSendRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Cache;

class MerchantSendCouponJob implements JobInterface
{

    public function fire($job, $sendId)
    {
        $storeCouponSendRepository = app()->make(StoreCouponSendRepository::class);
        $send = $storeCouponSendRepository->get((int)$sendId);
        if (!$send || $send->status == 1) {
            return $job->delete();
        }
        $cacheKey = '_send_coupon' . $sendId;
        $cache = Cache::store('file');
        if (!$cache->has($cacheKey)) {
            $send->status = -1;
            return $job->delete();
        }
        $storeCouponRepository = app()->make(StoreCouponRepository::class);
        $storeCouponUserRepository = app()->make(StoreCouponUserRepository::class);
        $coupon = $storeCouponRepository->get($send->coupon_id);
        if (!$coupon) {
            $send->status = -1;
            return $job->delete();
        }
        $uids = $cache->get($cacheKey);
        do {
            $install = [];
            foreach (array_splice($uids, -30) as $k => $uid) {
                $data = $storeCouponRepository->createData($coupon, $uid);
                $data['send_id'] = $sendId;
                $install[] = $data;
            }
            try {
                $storeCouponUserRepository->insertAll($install);
            } catch (\Exception $e) {
            }
            usleep(100);
        } while (count($uids));
        $send->status = 1;
        $send->save();
        return $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
