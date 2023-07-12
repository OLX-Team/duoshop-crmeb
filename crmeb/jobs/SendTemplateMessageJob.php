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

use crmeb\interfaces\JobInterface;
use think\facade\Log;
use think\queue\Job;
use crmeb\services\WechatTemplateService;
use app\common\repositories\user\UserRepository;
use crmeb\services\WechatTemplateMessageService;

class SendTemplateMessageJob implements JobInterface
{

    public function fire($job, $data)
    {
        $make = app()->make(WechatTemplateMessageService::class);
        try{
            $make->sendTemplate($data);
        }catch (\Exception $e){
            Log::info('公众号消息模板:' . $e->getMessage());
        }
        try{
            $make->subscribeSendTemplate($data);
        }catch (\Exception $e){
            Log::info('小程序消息模板:' . $e->getMessage());
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
