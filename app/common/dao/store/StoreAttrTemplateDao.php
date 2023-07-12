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


namespace app\common\dao\store;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\store\StoreAttrTemplate;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * Class StoreAttrTemplateDao
 * @package app\common\dao\store
 * @author xaboy
 * @day 2020-05-06
 */
class StoreAttrTemplateDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return StoreAttrTemplate::class;
    }

    /**
     * @param $merId
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-05-06
     */
    public function search($merId, array $where = [])
    {
        return StoreAttrTemplate::getDB()->when(isset($where['keyword']),function($query) use($where){
            $query->whereLike('template_name',"%{$where['keyword']}%");
        })->where('mer_id', $merId)->order('attr_template_id DESC');
    }

    /**
     * @param int $merId
     * @param int $id
     * @param null $except
     * @return bool
     * @author xaboy
     * @day 2020-04-15
     */
    public function merExists(int $merId, int $id, $except = null)
    {
        return $this->merFieldExists($merId, $this->getPk(), $id, $except);
    }

    /**
     * @param int $merId
     * @param $field
     * @param $value
     * @param null $except
     * @return bool
     * @author xaboy
     * @day 2020-04-15
     */
    public function merFieldExists(int $merId, $field, $value, $except = null)
    {
        return ($this->getModel())::getDB()->when($except, function ($query, $except) use ($field) {
                $query->where($field, '<>', $except);
            })->where('mer_id', $merId)->where($field, $value)->count() > 0;
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
        return ($this->getModel())::getDB()->where('mer_id', $merId)->find($id);
    }

    /**
     * @param int $id
     * @param int $merId
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-15
     */
    public function delete($id, $merId = 0)
    {
        $query = ($this->getModel())::getDB()->where('mer_id', $merId);
        if (is_array($id)) {
            $query->where($this->getPk(), 'in',$id);
        } else {
            $query->where($this->getPk(), $id);
        }
        return $query->delete();
    }

    public function getList($merId)
    {
        return ($this->getModel())::getDB()->where('mer_id',$merId)->field('attr_template_id,template_name,template_value')->select();
    }
}
