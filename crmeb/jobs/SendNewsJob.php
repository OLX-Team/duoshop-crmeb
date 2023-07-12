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
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\interfaces\JobInterface;
use crmeb\services\WechatService;
use think\queue\Job;

class SendNewsJob implements JobInterface
{

    public function fire($job, $data)
    {
        $wechatUserRepository = app()->make(WechatUserRepository::class);
        [$id, $news] = $data;
        if (!$id || !($openId = $wechatUserRepository->idByOpenId((int)$id))) {
            return  $job->delete();
        }
        try {
            WechatService::create()->staffTo($openId, WechatService::newsMessage($news));
        } catch (\Exception $e) {
            $job->failed($e);
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
