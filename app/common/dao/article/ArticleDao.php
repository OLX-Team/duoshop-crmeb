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


namespace app\common\dao\article;

use think\Collection;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use app\common\dao\BaseDao;
use app\common\model\article\Article;
use app\common\model\BaseModel;
use think\Model;

class ArticleDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return Article::class;
    }

    /**
     * @param int $mer_id
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getAll($mer_id = 0)
    {
        return Article::getDB()->with('content')->where('mer_id', $mer_id)->select();
    }

    /**
     * 搜索列表
     * @param $merId
     * @param array $where
     * @return BaseQuery
     * @author Qinii
     */
    public function search($merId,array $where)
    {
        $query = Article::getDB();
        if (isset($where['cid']) && $where['cid'] !== '') $query->where('cid', (int)$where['cid']);
        if (isset($where['title']) && $where['title'] !== '') $query->whereLike('title', "%{$where['title']}%");
        if (isset($where['status']) && $where['status'] !== '') $query->where('status', $where['status']);
        if (isset($where['wechat_news_id']) && $where['wechat_news_id'] !== '') $query->where('wechat_news_id', $where['wechat_news_id']);

        if (isset($where['article_id']) && $where['article_id'] !== ''){
            if (is_array($where['article_id'])) {
                $query->whereIn('article_id', $where['article_id']);
            } else {
                $query->where('article_id', $where['article_id']);
            }

        }


        $query->with(['content','articleCategory']);

        return $query->where('mer_id',$merId)->order('sort DESC,create_time DESC');
    }



    /**
     * 根据 字段名查询
     * @param int $merId
     * @param $field
     * @param $value
     * @param null $except
     * @return bool
     * @author Qinii
     */
    public function merFieldExists(int $merId, $field, $value, $except = null)
    {
        return ($this->getModel())::getDB()->when($except, function ($query, $except) use ($field) {
                $query->where($field, '<>', $except);
            })->where('mer_id', $merId)->where('wechat_news_id',0)->where($field, $value)->count() > 0;
    }

    /**
     * 查询一条
     * @param int $merId
     * @param int $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author Qinii
     *
     */
    public function get( $id, int $merId = 0)
    {
        return ($this->getModel())::getDB()->where('mer_id', $merId)->where('wechat_news_id',0)->with(['content','articleCategory'])->find($id);
    }

    /**
     * @param int $id
     * @param int $merId
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-20
     */
    public function delete(int $id, int $merId = 0)
    {
        $result = ($this->getModel())::getDB()->where('mer_id', $merId)
            ->where($this->getPk(),$id)
            ->with('content')
            ->find();
        return $result->together(['content'])->delete();
    }


    /**
     * 关联添加
     * @param array $data
     * @return BaseDao|Model|void
     * @author Qinii
     */
    public function create(array $data)
    {
        Db::transaction(function()use($data){
            $content = $data['content'];
            unset($data['content']);
            $article = $this->getModel()::create($data);
            $article->content()->save(['content' => $content]);
        });
    }

    /**
     * 关联更新
     * @param int $id
     * @param array $data
     * @return int|void
     * @author Qinii
     */
    public function update(int $id, array $data)
    {
        Db::transaction(function()use($id,$data){
            $content = $data['content'];
            unset($data['content']);

            $this->getModel()::where($this->getPk(),$id)->update($data);

            $article = $this->getModel()::find($id);
            $article->content->content = $content;
            $article->together(['content'])->save();
        });
    }

    /**
     * 根据字段获取 主键值
     * @param int $vale
     * @param null $field
     * @return array
     * @author Qinii
     */
    public function getKey(int $vale,$field = null)
    {
        return ($this->getModel())::getDB()->where($field,$vale)->column($this->getPk());
    }

    public function wechatNewIdByData($id)
    {
        return ($this->getModel())::getDB()->where('wechat_news_id', $id)->select();
    }

    public function switchStatus($id, $data)
    {
       return ($this->getModel())::getDB()->where($this->getPk(),$id)->update($data);
    }

    public function incVisit($id)
    {
        $this->getModel()::getDB()->where($this->getPk(),$id)->inc('visit',1);
    }
}
