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

class CommunityCategory extends BaseModel
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tablePk(): string
    {
        return 'category_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 10/26/21
     */
    public static function tableName(): string
    {
        return 'community_category';
    }

    public function children()
    {
        return $this->hasMany(CommunityTopic::class,'category_id','category_id')
            ->where('status',1)
            ->where('is_del',0)
            ->field('category_id,topic_id,topic_name,pic,sort,count_use')
            ->order('sort DESC,create_time DESC');
    }


    public function searchCateNameAttr($query, $value)
    {
        $query->whereLike('cate_name', "%{$value}%");
    }

    public function searchCategoryIdAttr($query, $value)
    {
        $query->where('category_id', $value);
    }

    public function searchIsShowAttr($query, $value)
    {
        $query->where('is_show', $value);
    }

}
