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


use app\common\repositories\user\UserRepository;
use crmeb\interfaces\JobInterface;

class AutoUserPosterJob implements JobInterface
{
    public function fire($job, $uid)
    {
        $userRepository = app()->make(UserRepository::class);
        $user = $userRepository->get($uid);
        if (!$user)
            $job->delete();
        try {
            $userRepository->routineSpreadImage($user);
        } catch (\Exception $e) {
        };
        try {
            $userRepository->wxSpreadImage($user);
        } catch (\Exception $e) {
        };
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
