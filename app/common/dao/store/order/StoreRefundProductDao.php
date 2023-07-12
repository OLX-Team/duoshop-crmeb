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


namespace app\common\dao\store\order;


use app\common\dao\BaseDao;
use app\common\model\store\order\StoreRefundProduct;

class StoreRefundProductDao extends BaseDao
{

    protected function getModel(): string
    {
        return StoreRefundProduct::class;
    }

    public function search(array $where)
    {
        $query = $this->getModel()::getDB()
            ->when(isset($where['order_id']) && $where['order_id'] !== '',function($query)use($where){
                $query->where('order_id',$where['order_id']);
            });

        return $query->order('create_time');
    }

    public function userRefundPrice(array $ids)
    {
        $lst = $this->getModel()::getDB()->alias('A')->leftJoin('StoreRefundOrder B', 'A.refund_order_id = B.refund_order_id')
            ->where('B.status', '>', -1)
            ->whereIn('A.order_product_id', $ids)->group('A.order_product_id')
            ->field('A.order_product_id, SUM(A.refund_price) as refund_price, SUM(A.platform_refund_price) as platform_refund_price, SUM(A.refund_postage) as refund_postage, SUM(A.refund_integral) as refund_integral')
            ->select()->toArray();
        $data = [];
        foreach ($lst as $item) {
            $data[$item['order_product_id']] = $item;
        }
        return $data;
    }
}
