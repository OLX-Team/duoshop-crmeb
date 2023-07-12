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


namespace app\common\model\system\notice;


use app\common\model\BaseModel;
use app\common\model\wechat\TemplateMessage;

/**
 * Class SystemNoticeLog
 * @package app\common\model\system\notice
 * @author xaboy
 * @day 2020/11/6
 */
class SystemNoticeConfig extends BaseModel
{

    /**
     * @return string|null
     * @author xaboy
     * @day 2020/11/6
     */
    public static function tablePk(): ?string
    {
        return 'notice_config_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/11/6
     */
    public static function tableName(): string
    {
        return 'system_notice_config';
    }

    public function wechatTemplate()
    {
        return $this->hasOne(TemplateMessage::class,'tempkey','wechat_tempkey');
    }

    public function routineTemplate()
    {
        return $this->hasOne(TemplateMessage::class,'tempkey','routine_tempkey');
    }

    public function searchKeywordAttr($query, $value)
    {
        $query->whereLike("notice_title|notice_key|notice_info","%{$value}%");
    }

    public function searchTypeAttr($query, $value)
    {
        $query->where("type",$value);
    }

    public function searchConstKeyAttr($query, $value)
    {
        $query->where("const_key",$value);
    }
}
