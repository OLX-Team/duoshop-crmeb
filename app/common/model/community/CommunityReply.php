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


namespace app\common\model\community;

use app\common\model\BaseModel;
use app\common\model\system\Relevance;
use app\common\model\user\User;
use app\common\repositories\system\RelevanceRepository;

class CommunityReply extends BaseModel
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tablePk(): string
    {
        return 'reply_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tableName(): string
    {
        return 'community_reply';
    }

    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'reply_id')->where('status', 1);
    }

    public function hasReply()
    {
        return $this->hasOne(self::class, 'pid', 'reply_id');
    }

    public function author()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function reply()
    {
        return $this->hasOne(User::class, 'uid', 're_uid');
    }

    public function community()
    {
        return $this->hasOne(Community::class, 'community_id', 'community_id');
    }

    public function isStart()
    {
        return $this->hasOne(Relevance::class, 'right_id', 'reply_id')->where('type', RelevanceRepository::TYPE_COMMUNITY_REPLY_START)->bind(['relevance_id']);
    }

    /**
     * 评论被删删除处理
     *
     * @param [string] $value
     * @return void
     */
    public function getContentAttr($value)
    {
        if ($this->is_del) $value = '[该评论已被删除]';
        return $value;
    }


    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    public function searchCommunityIdAttr($query, $value)
    {
        $query->where('community_id', $value);
    }

    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value);
    }

    public function searchPidAttr($query, $value)
    {
        $query->where('pid', $value);
    }
}
