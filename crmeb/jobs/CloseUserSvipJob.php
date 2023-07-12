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

class CloseUserSvipJob implements JobInterface
{
    public function fire($job, $type)
    {
        $make = app()->make(UserRepository::class);
        try {
            $uids = $make->search(['svip_type' => 1])->whereTime('svip_endtime','<=',time())->column('User.uid');
            $make->updates($uids,['is_svip' => 0]);
        } catch (\Exception $e) {
            Log::INFO('关闭付费会员失败：'.implode(',',$uids));
        };
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
