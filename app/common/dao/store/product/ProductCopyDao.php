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

namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\ProductCopy as model;

class ProductCopyDao extends BaseDao
{
    protected function getModel(): string
    {
        return model::class;
    }

    public function search(array $where)
    {
        return $this->getModel()::getDB()
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '',function($query)use($where){
                $query->where('mer_id',$where['mer_id']);
            })
            ->when(isset($where['type']) && $where['type'] !== '',function($query)use($where){
                if($where['type'] == 'copy'){
                    $query->where('type','in',['taobao','jd','copy']);
                } else {
                    $query->where('type',$where['type']);
                }
            })
            ->order('create_time DESC');
    }

    public function get2()
    {
       return $data =  model::where('store_product_copy_id','>',398)
            ->where('store_product_copy_id','<',467)->field('info')->select();

    }
}
