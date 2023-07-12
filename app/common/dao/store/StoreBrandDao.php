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
use app\common\model\store\StoreBrand as model;
use crmeb\traits\CategoresDao;

class StoreBrandDao extends BaseDao
{

    use CategoresDao;

    protected function getModel(): string
    {
        return model::class;
    }


    public function getAll()
    {
        $query = $this->getModel()::hasWhere('brandCategory',function($query){
                $query->where('is_show',1);
            });
        $query->where('StoreBrand.is_show',1);
        $list = $query->order('StoreBrand.sort DESC,StoreBrand.create_time DESC')->select()->toArray();
        array_push($list,[
            "brand_id" => 0,
            "brand_category_id" => 0,
            "brand_name" => "其他",
            "sort" => 999,
            "pic" => "",
            "is_show" => 1,
            "create_time" => "",
        ]);
        return $list;
    }


    public function merFieldExists($field, $value, $except = null)
    {
        return ($this->getModel())::getDB()
                ->when($except, function ($query, $except) use ($field) {
                    $query->where($field, '<>', $except);
                })
                ->where($field, $value)->count() > 0;
    }

    public function search(array $where)
    {
        $query = $this->getModel()::getDB();
        if(isset($where['brand_category_id']) && $where['brand_category_id'])
            $query->where('brand_category_id',$where['brand_category_id']);
        if(isset($where['brand_name']) && $where['brand_name'])
            $query->where('brand_name','like','%'.$where['brand_name'].'%');
        if((isset($where['ids']) && $where['ids']))
            $query->where($this->getPk(),'in',$where['ids']);
        return $query->order('sort DESC,create_time desc');

    }

}
