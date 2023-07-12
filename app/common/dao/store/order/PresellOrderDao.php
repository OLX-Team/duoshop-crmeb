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
use app\common\model\store\order\PresellOrder;

class PresellOrderDao extends BaseDao
{

    protected function getModel(): string
    {
        return PresellOrder::class;
    }

    public function search(array $where)
    {
        return PresellOrder::getDB()->when(isset($where['pay_type']) && $where['pay_type'] !== '', function ($query) use ($where) {
            $query->whereIn('pay_type', $where['pay_type']);
        })->when(isset($where['paid']) && $where['paid'] !== '', function ($query) use ($where) {
            $query->where('paid', $where['paid']);
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        })->when(isset($where['order_ids']) && $where['order_ids'] !== '', function ($query) use ($where) {
            $query->where('order_id','in',$where['order_ids']);
        });
    }

    public function userOrder($uid, $orderId)
    {
        return PresellOrder::getDB()->where('uid', $uid)->where('order_id', $orderId)->find();
    }

    /**
     * @param $time
     * @return array
     * @author xaboy
     * @day 2020/11/3
     */
    public function getTimeOutIds($time)
    {
        return PresellOrder::getDB()->where('status', 1)->where('paid', 0)
            ->where('final_end_time', '<', $time)->column('presell_order_id');
    }

    public function sendSmsIds($date)
    {
        return PresellOrder::getDB()->where('status', 1)->where('paid', 0)
            ->whereLike('final_start_time', $date . '%')->column('order_id');
    }
}
