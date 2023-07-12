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


use app\common\repositories\system\notice\SystemNoticeConfigRepository;
use crmeb\interfaces\JobInterface;
use crmeb\services\SmsService;
use crmeb\services\WechatTemplateMessageService;
use think\facade\Log;

class SendSmsJob implements JobInterface
{

    public function fire($job, $data)
    {
        $status = app()->make(SystemNoticeConfigRepository::class)->getNoticeStatusByConstKey($data['tempId']);
        if ($status['notice_sms'] == 1) {
            try {
                SmsService::sendMessage($data);
            } catch (\Exception $e) {
                Log::info('发送短信失败' . var_export($data, 1) . $e->getMessage());
            }
        }
        if ($status['notice_wechat'] == 1) {
            try {
                app()->make(WechatTemplateMessageService::class)->sendTemplate($data);
            } catch (\Exception $e) {
                Log::info('模板消息发送失败' . var_export($data, 1) . $e->getMessage());
            }
        }
        if ($status['notice_routine'] == 1) {
            try {
                Log::info('订阅消息发送数据' . var_export($data, 1));
                app()->make(WechatTemplateMessageService::class)->subscribeSendTemplate($data);
            } catch (\Exception $e) {
                Log::info('订阅消息发送失败' . var_export($data, 1) . $e->getMessage());
            }
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
