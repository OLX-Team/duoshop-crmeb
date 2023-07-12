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


use app\common\dao\BaseDao;
use app\common\model\article\ArticleCategory;
use app\common\model\BaseModel;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * Class ArticleCategoryDao
 * @package app\common\dao\article
 * @author xaboy
 * @day 2020-04-20
 */
class ArticleCategoryDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return ArticleCategory::class;
    }

    /**
     * @param int $mer_id
     * @return array
     * @author xaboy
     * @day 2020-04-20
     */
    public function getAllOptions($mer_id = 0)
    {
        return ArticleCategory::getDB()->where('mer_id', $mer_id)->order('sort DESC')->column('pid,title', $this->getPk());
    }

    /**
     * @param int $mer_id
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-20
     */
    public function getAll($mer_id = 0,$status = null)
    {
        return ArticleCategory::getDB()->where('mer_id', $mer_id)->when($status,function($query)use($status){
            $query->where('status',$status);
        })->order('sort DESC')->select();
    }

    /**
     * @param array $where
     * @return \think\db\BaseQuery
     * @author xaboy
     * @day 2020/9/18
     */
    public function search(array $where)
    {
        return ArticleCategory::getDB()->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('status', $where['status']);
        })->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use ($where) {
            $query->where('pid', $where['pid']);
        })->order('sort DESC, article_category_id DESC');
    }

    /**
     * @param int $merId
     * @param $field
     * @param $value
     * @param null $except
     * @return bool
     * @author xaboy
     * @day 2020-04-20
     */
    public function merFieldExists(int $merId, $field, $value, $except = null)
    {
        return ($this->getModel())::getDB()->when($except, function ($query, $except) use ($field) {
                $query->where($field, '<>', $except);
            })->where('mer_id', $merId)->where($field, $value)->count() > 0;
    }

    /**
     * @param int $merId
     * @param int $id
     * @param null $except
     * @return bool
     * @author xaboy
     * @day 2020-04-20
     */
    public function merExists(int $merId, int $id, $except = null)
    {
        return $this->merFieldExists($merId, $this->getPk(), $id, $except);
    }

    /**
     * @param int $id
     * @param int $merId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-15
     */
    public function get( $id, $merId = 0)
    {
        return ($this->getModel())::getDB()->where('mer_id', 0)->find($id);
    }

    /**
     * @param int $id
     * @param int $merId
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-20
     */
    public function delete(int $id, $merId = 0)
    {
        return ($this->getModel())::getDB()->where($this->getPk(), $id)->where('mer_id', $merId)->delete();
    }
}
