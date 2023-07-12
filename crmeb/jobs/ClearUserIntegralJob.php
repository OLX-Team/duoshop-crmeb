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

use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Db;
use think\facade\Log;

class ClearUserIntegralJob implements JobInterface
{

    public function fire($job, $data)
    {
        try {
            $user = app()->make(UserRepository::class)->get($data['uid']);
            if ($user && $user->integral > 0) {
                $validIntegral = app()->make(UserBillRepository::class)->validIntegral($data['uid'], $data['startTime'], $data['endTime']);
                if ($user->integral > $validIntegral) {
                    $clear = bcsub($user->integral, $validIntegral, 0);
                    $user->integral = $validIntegral;
                    app()->make(UserBillRepository::class)->decBill($user->uid, 'integral', 'timeout', [
                        'link_id' => 0,
                        'status' => 1,
                        'title' => '积分过期',
                        'number' => $clear,
                        'mark' => $clear . '积分已过期',
                        'balance' => $user->integral
                    ]);
                    $user->save();
                }
            }
        } catch (\Exception $e) {
            Log::info('用户ID：' . $data['uid'] . '积分清理失败');
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
