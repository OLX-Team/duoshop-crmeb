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


namespace app\validate\admin;


use think\Validate;

class SystemNoticeConfigValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'notice_title|通知名称' => 'require',
        'notice_key|消息键名称' => 'require',
        'notice_info|消息说明' => 'max:50',
        'notice_sys|站内消息' => 'in:0,1,-1',
        'notice_wechat|公众号模板消息' => 'in:0,1,-1',
        'notice_routine|小程序订阅消息' => 'in:0,1,-1',
        'notice_sms|短信消息' => 'in:0,1,-1'
    ];

}
