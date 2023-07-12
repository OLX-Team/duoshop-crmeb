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

class CommunityTopic extends BaseModel
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tablePk(): string
    {
        return 'topic_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tableName(): string
    {
        return 'community_topic';
    }

    public function category()
    {
        return $this->hasOne(CommunityCategory::class,'category_id','category_id');
    }


    public function searchTopicNameAttr($query, $value)
    {
        $query->whereLike('topic_name', "%{$value}%");
    }

    public function searchTopicIdAttr($query, $value)
    {
        $query->where('topic_id', $value);
    }

    public function searchCategoryIdAttr($query, $value)
    {
        $query->where('category_id', $value);
    }

    public function searchIsHotAttr($query, $value)
    {
        $query->where('is_hot', $value);
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }


}
