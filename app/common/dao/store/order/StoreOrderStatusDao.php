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
use app\common\model\BaseModel;
use app\common\model\store\order\StoreOrderStatus;

/**
 * Class StoreOrderStatusDao
 * @package app\common\dao\store\order
 * @author xaboy
 * @day 2020/6/12
 */
class StoreOrderStatusDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/6/12
     */
    protected function getModel(): string
    {
        return StoreOrderStatus::class;
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020/6/12
     */
    public function search($where)
    {
        $query = ($this->getModel()::getDB())
            ->when(isset($where['id']) && $where['id'] !== '', function($query) use($where){
                $query->where('order_id', $where['id']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function($query) use($where){
                $query->where('type', $where['type']);
            })
            ->when(isset($where['user_type']) && $where['user_type'] !== '', function($query) use($where){
                $query->where('user_type', $where['user_type']);
            })
            ->when(isset($where['date']) && $where['date'] !== '', function($query) use($where){
                getModelTime($query, $where['date'],'change_time');
            });
        return $query;
    }

    public function getTimeoutDeliveryOrder($end)
    {
        return StoreOrderStatus::getDB()->alias('A')->leftJoin('StoreOrder B', 'A.order_id = B.order_id')
            ->whereIn('A.change_type', ['delivery_0', 'delivery_1', 'delivery_2'])
            ->where('A.change_time', '<', $end)->where('B.paid', 1)->where('B.status', 1)
            ->column('A.order_id');
    }
}
