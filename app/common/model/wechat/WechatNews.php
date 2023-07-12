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


namespace app\common\model\wechat;


use app\common\model\article\Article;
use app\common\model\BaseModel;

/**
 * Class WechatReply
 * @package app\common\model\wechat
 * @author xaboy
 * @day 2020-04-24
 */
class WechatNews extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'wechat_news_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'wechat_news';
    }

    /**
     * @param $val
     * @return mixed
     * @author xaboy
     * @day 2020-04-24
     */
    public function getDataAttr($val)
    {
        return json_decode($val, true);
    }

    /**
     * @param $val
     * @return false|string
     * @author xaboy
     * @day 2020-04-24
     */
    public function setDataAttr($val)
    {
        return json_encode($val);
    }

    /**
     * 一对多关联
     * @return \think\model\relation\BelongsTo
     * @author Qinii
     */
    public function article()
    {
        return $this->hasMany(Article::class ,'wechat_news_id','wechat_news_id')
            ->field('article_id,title,author,image_input,synopsis,wechat_news_id');
    }
}
