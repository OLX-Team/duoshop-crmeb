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


namespace app\common\dao\user;

use app\common\dao\BaseDao;
use app\common\model\user\LabelRule;
use app\common\model\user\UserOrder;

class UserOrderDao extends BaseDao
{

    protected function getModel(): string
    {
        return UserOrder::class;
    }

    public function search(array $where)
    {
        return UserOrder::hasWhere('user',function($query)use($where){
                $query->when(isset($where['uid']) && $where['uid'] != '',function($query) use($where){
                    $query->where('uid', $where['uid']);
                })
                ->when(isset($where['keyword']) && $where['keyword'] != '',function($query) use($where){
                    $query->whereLike('nickname', "%{$where['keyword']}%");
                })
                ->when(isset($where['phone']) && $where['phone'] != '',function($query) use($where){
                    $query->where('phone', $where['phone']);
                });
                $query->where(true);
            })
            ->when(isset($where['order_sn']) && $where['order_sn'] !== '', function ($query) use ($where) {
                $query->whereLike('order_sn', "%{$where['order_sn']}%");
            })
            ->when(isset($where['title']) && $where['title'] !== '', function ($query) use ($where) {
                $query->whereLike('title', "%{$where['title']}%");
            })
            ->when(isset($where['order_type']) && $where['order_type'] !== '', function ($query) use ($where) {
                $query->where('order_type', $where['order_type']);
            })
            ->when(isset($where['paid']) && $where['paid'] !== '', function ($query) use ($where) {
                $query->where('paid', $where['paid']);
            })
            ->when(isset($where['pay_type']) && $where['pay_type'] !== '', function ($query) use ($where) {
                $query->where('pay_type', $where['pay_type']);
            })
            ->when(isset($where['pay_time']) && $where['pay_time'] !== '', function ($query) use ($where) {
                $query->whereDay('pay_time', $where['pay_time']);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->whereDay('mer_id', $where['mer_id']);
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'UserOrder.create_time');
            })
            ;
    }
}
