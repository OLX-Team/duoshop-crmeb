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
use app\common\model\store\product\ProductGroupBuying;

class ProductGroupBuyingDao extends  BaseDao
{
    public function getModel(): string
    {
        return ProductGroupBuying::class;
    }


    public function search($where)
    {
        $query = ProductGroupBuying::getDb()->alias('B')->join('StoreProductGroup G','B.product_group_id = G.product_group_id');

        $query
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function($query)use($where){
                $query->where('B.mer_id',$where['mer_id']);
            })
            ->when(isset($where['date']) && $where['date'] , function($query)use($where){
                getModelTime($query,$where['date'],'B.create_time');
            })
            ->when(isset($where['status']) && $where['status'] !== '', function($query)use($where){
                $query->where('B.status',$where['status']);
            })
            ->when(isset($where['user_name']) && $where['user_name'] !== '', function($query)use($where){
                $query->join('StoreProductGroupUser U','U.group_buying_id = B.group_buying_id')->where('is_initiator',1)
                    ->whereLike('uid|nickname',"%{$where['user_name']}%");
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '' , function($query)use($where){
                $query->join('StoreProduct P','G.product_id = P.product_id')
                    ->whereLike('B.group_buying_id|P.product_id|store_name',"%{$where['keyword']}%");
            })
            ->when(isset($where['is_trader']) && $where['is_trader'] !== '', function($query)use($where){
                $query->join('Merchant M','M.mer_id = B.mer_id')->where('is_trader',$where['is_trader']);
            })
        ;

        return $query;
    }
}
