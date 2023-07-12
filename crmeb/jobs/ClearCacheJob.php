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


use app\common\repositories\system\CacheRepository;
use app\common\repositories\user\UserRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class ClearCacheJob implements JobInterface
{
    public function fire($job, $type)
    {
        $make = app()->make(CacheRepository::class);
        try {
            $make->clearCacheAll($type);
        } catch (\Exception $e) {
            Log::INFO('清除缓存失败：'.$type);
        };

        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
