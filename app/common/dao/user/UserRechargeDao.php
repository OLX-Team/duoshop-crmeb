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
use app\common\model\user\UserRecharge;
use app\common\repositories\store\order\StoreOrderRepository;
use think\db\BaseQuery;

/**
 * Class UserRechargeDao
 * @package app\common\dao\user
 * @author xaboy
 * @day 2020/6/2
 */
class UserRechargeDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/6/2
     */
    protected function getModel(): string
    {
        return UserRecharge::class;
    }

    public function createOrderId($uid)
    {
        $count = (int)UserRecharge::getDB()->where('uid', $uid)->where('create_time', '>=', date("Y-m-d"))->where('create_time', '<', date("Y-m-d", strtotime('+1 day')))->count();
        return StoreOrderRepository::TYPE_SN_USER_RECHARGE . date('YmdHis', time()) . ($uid . $count);
    }

    public function userRechargePrice($uid)
    {
        return UserRecharge::getDB()->where('uid', $uid)->where('paid', 1)->sum('price');
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/23
     */
    public function searchJoinQuery(array $where)
    {
        return UserRecharge::getDB()->alias('a')->join('User b', 'a.uid = b.uid')
            ->field('a.paid,a.order_id,a.recharge_id,b.nickname,b.avatar,a.price,a.give_price,a.recharge_type,a.pay_time')
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->whereLike('b.nickname|a.order_id', "%{$where['keyword']}%");
            })->when(isset($where['paid']) && $where['paid'] !== '', function ($query) use ($where) {
                $query->whereLike('a.paid', $where['paid']);
            })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'a.create_time');
            });
    }

    public function totalPayPrice()
    {
        return UserRecharge::getDB()->where('paid', 1)->sum('price');
    }

    public function totalRefundPrice()
    {
        return UserRecharge::getDB()->where('paid', 1)->sum('refund_price');
    }

    public function totalRoutinePrice()
    {
        return UserRecharge::getDB()->where('paid', 1)->where('recharge_type', 'routine')->sum('price');
    }

    public function totalWxPrice()
    {
        return UserRecharge::getDB()->where('paid', 1)->whereIn('recharge_type', ['h5', 'wechat'])->sum('price');
    }
}
