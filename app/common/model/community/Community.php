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
use app\common\model\store\product\Spu;
use app\common\model\system\Relevance;
use app\common\model\user\User;
use app\common\repositories\system\RelevanceRepository;

class Community extends BaseModel
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tablePk(): string
    {
        return 'community_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tableName(): string
    {
        return 'community';
    }

    public function getImageAttr($value)
    {
        return explode(',',$value);
    }

    public function author()
    {
        return $this->hasOne(User::class,'uid','uid');
    }

    public function topic()
    {
        return $this->hasOne(CommunityTopic::class,'topic_id','topic_id');
    }

    public function reply()
    {
        return $this->hasMany(CommunityReply::class,'community_id','community_id');
    }

    public function relevance()
    {
        return $this->hasMany(Relevance::class, 'left_id','community_id')
            ->where('type',RelevanceRepository::TYPE_COMMUNITY_PRODUCT);
    }

    /*
     *  右侧为内容ID的
     */
    public function relevanceRight()
    {
        return $this->hasMany(Relevance::class, 'right_id','community_id');
    }

    public function isStart()
    {
        return $this->hasOne(Relevance::class, 'right_id','community_id')->where('type', RelevanceRepository::TYPE_COMMUNITY_START)->bind(['relevance_id']);
    }

    public function isFans()
    {
        return $this->hasOne(Relevance::class, 'right_id','uid')->where('type', RelevanceRepository::TYPE_COMMUNITY_FANS)->bind(['is_fans' => 'right_id']);
    }

    public function category()
    {
        return $this->hasOne(CommunityCategory::class, 'category_id','category_id');
    }

    public function getTimeAttr()
    {
        return date('m月d日',strtotime($this->create_time));
    }

    public function getCountReplyAttr()
    {
        return CommunityReply::where('community_id',$this->community_id)->where('status',1)->count();
    }

    public function searchTopicIdAttr($query, $value)
    {
        $query->where('topic_id', $value);
    }

    public function searchTitleAttr($query, $value)
    {
        $query->whereLike('title', "%{$value}%");
    }

    public function searchKeywordAttr($query, $value)
    {
        $query->whereLike('title|content', "%{$value}%");
    }

    public function searchCategoryIdAttr($query, $value)
    {
        $query->where('category_id', $value);
    }

    public function searchIsShowAttr($query, $value)
    {
        $query->where('is_show', $value);
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    public function searchIsHotAttr($query, $value)
    {
        $query->where('is_hot', $value);
    }

    public function searchUidsAttr($query, $value)
    {
        $query->whereIn('uid', $value);
    }

    public function searchSpuIdAttr($query, $value)
    {
        $id = Relevance::where('right_id',$value)
            ->where('type',RelevanceRepository::TYPE_COMMUNITY_PRODUCT)
            ->column('left_id');
        $query->where('community_id','in', $id);
    }

    public function searchIsTypeAttr($query, $value)
    {
        $query->whereIn('is_type', $value);
    }

    public function searchCommunityIdAttr($query, $value)
    {
        $query->where('community_id', $value);
    }

    public function searchNotIdAttr($query, $value)
    {
        $query->where('community_id', '<>',$value);
    }

}
