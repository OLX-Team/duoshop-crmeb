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
use app\common\model\store\order\StoreOrderProfitsharing;

class StoreOrderProfitsharingDao extends BaseDao
{
    protected function getModel(): string
    {
        return StoreOrderProfitsharing::class;
    }

    public function getOrderSn()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $orderId = 'pr' . $msectime . random_int(10000, max(intval($msec * 10000) + 10000, 98369));
        return $orderId;
    }

    public function search(array $where)
    {
        return StoreOrderProfitsharing::getDB()->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        })->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
            $query->where('order_id', $where['order_id']);
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', $where['type']);
        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('status', $where['status']);
        })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['date']);
        })->when(isset($where['profit_date']) && $where['profit_date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['profit_date'], 'profitsharing_time');
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('keyword', "%{$where['keyword']}%");
        });
    }

    public function getAutoProfitsharing($time)
    {
        return StoreOrderProfitsharing::getDB()->alias('A')->join('StoreOrder B', 'A.order_id = B.order_id', 'left')
            ->where(function ($query) {
                $query->where('B.status', '>', 1)->whereOr('B.status', -1);
            })->where('A.status', 0)->where(function ($query) use ($time) {
                $query->whereNotNull('B.verify_time')->where('B.verify_time', '<', $time);
            })->column('A.profitsharing_id');
    }
}
