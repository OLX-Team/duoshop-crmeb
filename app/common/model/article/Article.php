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


namespace app\common\model\article;


use app\common\model\BaseModel;

class Article extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'article_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'article';
    }

    /**
     * @return \think\model\relation\HasOne
     * @author Qinii
     */
    public function content()
    {
        return $this->hasOne(ArticleContent::class,'article_content_id','article_id');
    }

    /**
     * @return \think\model\relation\HasOne
     * @author Qinii
     */
    public function articleCategory()
    {
        return $this->hasOne(ArticleCategory::class ,'article_category_id','cid')
            ->field('article_category_id,title');
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }
}
