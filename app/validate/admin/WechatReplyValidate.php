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

/**
 * Class WechatReplyValidate
 * @package app\validate\admin
 * @author xaboy
 * @day 2020-04-27
 */
class WechatReplyValidate extends Validate
{
    /**
     * @var bool
     */
    protected $failException = true;

    /**
     * @var array
     */
    protected $rule = [
        'type|类型' => 'require|in:text,image,news,voice',
        'key|关键字' => 'require|max:32',
        'data|回复内容' => 'require|array',
        'status|开启状态' => 'require|in:0,1'
    ];

    /**
     * @return $this
     * @author xaboy
     * @day 2020-04-27
     */
    public function isUpdate()
    {
        unset($this->rule['key|关键字']);
        return $this;
    }
}
