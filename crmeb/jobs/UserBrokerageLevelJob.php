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


use app\common\repositories\user\UserBrokerageRepository;
use app\common\repositories\user\UserRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class UserBrokerageLevelJob implements JobInterface
{
    public function fire($job, $data)
    {
        try {
            $user = app()->make(UserRepository::class)->get($data['uid']);
            if ($user) {
                $flag = true;
                if ($data['type'] == 'spread_money') {
                    $user->spread_pay_price = bcadd($user->spread_pay_price, $data['inc'], 2);
                } else if ($data['type'] == 'spread_pay_num') {
                    $user->spread_pay_count = bcadd($user->spread_pay_count, $data['inc'], 0);
                } else {
                    $flag = false;
                }
                if ($flag) {
                    $user->save();
                }
            }
            if ($user && $user->is_promoter) {
                app()->make(UserBrokerageRepository::class)->inc($user, $data['type'], $data['inc']);
            }
        } catch (\Exception $e) {
            Log::info('分销等级同步失败: ' . var_export($data, 1) . $e->getMessage());
        }
        $job->delete();
    }

    public function failed($data)
    {
    }
}
