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


use app\common\model\user\User;
use app\common\repositories\store\IntegralRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserRepository;
use crmeb\interfaces\ListenerInterface;
use crmeb\jobs\ClearUserIntegralJob;
use crmeb\jobs\SendSmsJob;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;

class AutoClearIntegralListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        //TODO 自动解冻积分
        $this->tick(1000 * 60 * 20, function () {
            request()->clearCache();
            if (!systemConfig('integral_status')) return;
            $make = app()->make(IntegralRepository::class);
            $end = $make->getTimeoutDay();
            if ($end == strtotime(date('Y-m-d') . ' 00:00:00')) {
                $start = $make->getInvalidDay();
                $make->clearTimeoutDay();
                if ($start) {
                    $startTime = date('Y-m-d H:i:s', $start);
                    $endTime = date('Y-m-d H:i:s', $end);
                    User::getDB()->where('integral', '>', 0)->field('uid')->chunk(1000, function ($users) use ($startTime, $endTime) {
                        foreach ($users as $user) {
                            $uid = $user['uid'];
                            Queue::later(1800, ClearUserIntegralJob::class, compact('uid', 'startTime', 'endTime'));
                        }
                        usleep(100);
                    });
                }
            } else if ($end < strtotime('+15 day')) {
                $make1 = app()->make(UserBillRepository::class);
                $invalidDay = $make->getInvalidDay();
                $cache = Cache::store('file');
                $checkKey = 'integral_check';
                if ($cache->has($checkKey)) {
                    return;
                }
                $endTime = $end;
                $startTime = date('Y-m-d H:i:s', $invalidDay);
                User::getDB()->where('integral', '>', 0)->where('phone', '<>', '')->field('uid,phone,integral')->chunk(1000, function ($users) use ($endTime, $startTime, $invalidDay, $end, $make1, $cache) {
                    foreach ($users as $user) {
                        $cacheKey = 'integral_sms' . $user['uid'];
                        if ($cache->has($cacheKey)) {
                            continue;
                        }
                        $integral = $user['integral'];
                        if ($integral > 0 && $invalidDay) {
                            $validIntegral = $make1->validIntegral($user['uid'], $startTime, $endTime);
                            if ($integral > $validIntegral) {
                                $nextClearIntegral = (int)bcsub($integral, $validIntegral, 0);
                                $cache->set($cacheKey, 1, 3600 * 24 * 20);
                                Queue::push(SendSmsJob::class, [
                                    'tempId' => 'INTEGRAL_INVALID',
                                    'id' => [
                                        'integral' => $nextClearIntegral,
                                        'phone' => $user['phone'],
                                        'date' => $endTime
                                    ]
                                ]);
                                continue;
                            }
                        }
                        $cache->set($cacheKey, 1, 3600 * 24 * 2);
                    }
                    usleep(200);
                });
                $cache->set($checkKey, 1, 3600 * 24 * 1);
            }
        });
    }
}
