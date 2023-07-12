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


namespace app\validate\merchant;


use think\Validate;

class BroadcastRoomValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'name|直播间名字' => 'require|min:3|max:17',
        'cover_img|背景图' => 'require',
        'share_img|分享图' => 'require',
        'anchor_name|主播昵称' => 'require|min:4|max:15',
        'anchor_wechat|主播微信号' => 'require',
        'phone|联系电话' => 'require|mobile',
        'start_time|直播时间' => 'require|array|length:2|checkTime',
        'type|直播间类型' => 'require|in:0,1',
        'screen_type|显示样式' => 'require|in:0,1',
        'close_like|是否开启点赞' => 'require|in:0,1',
        'close_goods|是否开启货架' => 'require|in:0,1',
        'close_comment|是否开启评论' => 'require|in:0,1',
        'replay_status|是否开启回放' => 'require|in:0,1',
        'close_share|是否开启分享' => 'require|in:0,1',
        'close_kf|是否开启客服' => 'require|in:0,1',
    ];

    protected function checkTime($value)
    {
        $start = strtotime($value[0]);
        $end = strtotime($value[1]);
        if ($end < $start) return '请选择正确的直播时间';
        if ($start < strtotime('+ 15 minutes')) return '开播时间必须大于当前时间15分钟';
        if ($start >= strtotime('+ 6 month')) return '开播时间不能在6个月后';
        if (($end - $start) < (60 * 30)) return '直播时间不得小于30分钟';
        if (($end - $start) > (60 * 60 * 24)) return '直播时间不得超过24小时';
        return true;
    }
}
