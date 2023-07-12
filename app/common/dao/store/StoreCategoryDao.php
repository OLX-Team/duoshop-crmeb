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
use app\common\model\store\StoreCategory as model;
use crmeb\traits\CategoresDao;

class StoreCategoryDao extends BaseDao
{

    use CategoresDao;

    protected function getModel(): string
    {
        return model::class;
    }

    public function getAll($mer_id = 0,$status = null, $type = 0)
    {
        return $this->getModel()::getDB()
            ->where('mer_id', $mer_id)
            ->where('type',$type)
            ->when(($status !== null),function($query)use($status){
                $query->where($this->getStatus(),$status);
            })
            ->order('sort DESC,'.$this->getPk().' DESC')
            ->select()
            ->append(['has_product']);
    }

    public function findChildrenId($id)
    {
        return model::getDB()->whereLike('path', '%/'. $id . '/%')->column('store_category_id');
    }

    public function selectChildrenId(array $ids)
    {
        if (!is_array($ids) || empty($ids))  return [];
        $query = model::getDB()->where(function($query) use($ids){
            foreach ($ids as $id) {
                $query->whereOr('path', 'like','%/'. $id . '/%');
            }
        });
        return $query->column('store_category_id');
    }


    public function fieldExistsList(?int $merId,$field,$value,$except = null)
    {
        return ($this->getModel()::getDB())->when($except ,function($query)use($field,$except){
            $query->where($field,'<>',$except);
        })->when(($merId !== null) ,function($query)use($merId){
            $query->where('mer_id',$merId);
        })->where($field,$value);

    }

    public function getTwoLevel($merId = 0)
    {
        $pid = model::getDB()->where('pid', 0)->where('is_show',1)->where('mer_id', $merId)->order('sort DESC')->column('store_category_id');
        return model::getDB()->whereIn('pid', $pid)->where('is_show', 1)->where('mer_id', $merId)->limit(20)->order('sort DESC')->column('store_category_id,cate_name,pid');
    }

    public function children($pid, $merId = 0)
    {
        return model::getDB()->where('pid', $pid)->where('mer_id', $merId)->where('is_show', 1)->order('sort DESC')->column('store_category_id,cate_name,pic');
    }

    public function allChildren($id)
    {
        $path = model::getDB()->where('store_category_id', is_array($id) ? 'IN' : '=', $id)->where('mer_id', 0)->column('path', 'store_category_id');
        if (!count($path)) return [];
        return model::getDB()->where(function ($query) use ($path) {
            foreach ($path as $k => $v) {
                $query->whereOr('path', 'LIKE', "$v$k/%");
            }
        })->where('mer_id', 0)->order('sort DESC')->column('store_category_id');
    }

    public function idsByAllChildren(array $ids)
    {
        $paths = model::getDB()->whereIn('store_category_id', $ids)->where('mer_id', 0)->column('path');
        if (!count($paths)) return [];
        return model::getDB()->where(function ($query) use ($paths) {
            foreach ($paths as $path) {
                $query->whereOr('path', 'LIKE', "$path%");
            }
        })->where('mer_id', 0)->order('sort DESC')->column('store_category_id');
    }

    public function getMaxLevel($merId = null)
    {
        if($merId) return 2;
        return 3;
    }

    public function searchLevelAttr($query, $value)
    {
        $query->where('level', $value);
    }

    public function clear(int $id, string $field)
    {
        $this->getModel()::getDB()->where($field, $id)->delete();
    }
}
