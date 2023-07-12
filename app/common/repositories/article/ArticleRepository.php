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


namespace app\common\repositories\article;


use app\common\dao\article\ArticleDao;
use app\common\model\article\ArticleContent;
use app\common\repositories\BaseRepository;
use think\facade\Db;

class ArticleRepository extends BaseRepository
{
    public function __construct(ArticleDao $dao)
    {
        $this->dao = $dao;
    }

    public function getFormatList($merId = 0)
    {
        return $this->dao->getAll($merId)->toArray();
    }

    /**
     * @param int $merId
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     */
    public function search(int $merId, array $where, $page, $limit)
    {
        $where['wechat_news_id'] = 0;
        $query = $this->dao->search($merId, $where)->order('create_time DESC');
        $count = $query->count($this->dao->getPk());
        $list = $query->page($page, $limit)->hidden(['update_time'])->select();
        return compact('count', 'list');
    }

    /**
     * 根据主键查询
     * @param int $merId
     * @param int $id
     * @param null $except
     * @return bool
     * @author Qinii
     */
    public function merExists(int $merId, int $id, $except = null)
    {
        return $this->dao->merFieldExists($merId, $this->dao->getPk(), $id, $except);
    }


    public function merApiExists(int $id)
    {
        return $this->dao->getWhere([$this->dao->getPk() => $id,'status' => 1]);
    }

    public function clearByNewId($newId)
    {
        Db::transaction(function()use($newId){
            $article_id = $this->dao->search(0,['wechat_news_id' => $newId])->column('article_id');
            foreach ($article_id as $item){
                $this->dao->delete($item,0);
            }
        });
    }
}
