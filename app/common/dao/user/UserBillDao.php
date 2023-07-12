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
use app\common\model\BaseModel;
use app\common\model\user\UserBill;

/**
 * Class UserBillDao
 * @package app\common\dao\user
 * @author xaboy
 * @day 2020/6/22
 */
class UserBillDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return UserBill::class;
    }

    /**
     * @param array $where
     * @param $data
     * @return int
     * @throws \think\db\exception\DbException
     * @author xaboy
     * @day 2020/6/22
     */
    public function updateBill(array $where, $data)
    {
        return UserBill::getDB()->where($where)->limit(1)->update($data);
    }

    /**
     * @param $time
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/22
     */
    public function getTimeoutBrokerageBill($time)
    {
        return UserBill::getDB()->where('create_time', '<=', $time)->where('category', 'brokerage')
            ->whereIn('type', ['order_one', 'order_two'])->with('user')->where('status', 0)->select();
    }

    public function getTimeoutIntegralBill($time)
    {
        return UserBill::getDB()->where('create_time', '<=', $time)->where('category', 'integral')
            ->where('type', 'lock')->with('user')->where('status', 0)->select();
    }

    public function getTimeoutMerchantMoneyBill($time)
    {
        return UserBill::getDB()->where('create_time', '<=', $time)->where('category', 'mer_computed_money')->where('type','order')
            ->where('status', 0)->select();
    }

    public function refundMerchantMoney($order_id, $type, $mer_id)
    {
        return UserBill::getDB()->where('link_id', $order_id)->where('mer_id', $mer_id)
            ->where('category', 'mer_refund_money')->where('type', $type)->sum('number');
    }

    public function merchantLickMoney($merId = null)
    {
        $lst = UserBill::getDB()->where('category', 'mer_lock_money')->when($merId, function ($query, $val) {
            $query->where('mer_id', $val);
        })->where('status', 0)->select()->toArray();
        $lockMoney = 0;
        if (count($lst)) {
            $lockMoney = -1 * UserBill::getDB()->whereIn('link_id', array_column($lst, 'link_id'))
                    ->where('category', 'mer_refund_money')->sum('number');
        }
        foreach ($lst as $bill) {
            $lockMoney = bcadd($lockMoney, $bill['number'], 2);
        }
        $lockMoney = bcadd($lockMoney, UserBill::getDB()
            ->where('category', 'mer_computed_money')->when($merId, function ($query, $val) {
                $query->where('mer_id', $val);
            })->where('status', 0)->where('type', 'order')->sum('number'), 2);
        return $lockMoney;
    }

    /**
     * @param $uid
     * @return float
     * @author xaboy
     * @day 2020/6/22
     */
    public function lockBrokerage($uid)
    {
        $lst = UserBill::getDB()->where('category', 'brokerage')
            ->whereIn('type', ['order_one', 'order_two'])->where('uid', $uid)->where('status', 0)->field('link_id,number')->select()->toArray();
        $refundPrice = 0;
        if (count($lst)) {
            $refundPrice = -1 * UserBill::getDB()->whereIn('link_id', array_column($lst, 'link_id'))->where('uid', $uid)
                    ->where('category', 'brokerage')->whereIn('type', ['refund_two', 'refund_one'])->sum('number');
        }
        foreach ($lst as $bill) {
            $refundPrice = bcadd($refundPrice, $bill['number'], 2);
        }
        return $refundPrice;
    }

    public function lockIntegral($uid = null, $order_id = null)
    {
        $lst = UserBill::getDB()->where('category', 'integral')
            ->where('type', 'lock')->when($order_id, function ($query, $order_id) {
                $query->where('link_id', $order_id);
            })->when($uid, function ($query, $uid) {
                $query->where('uid', $uid);
            })->where('status', 0)->field('link_id,number')->select()->toArray();
        $lockIntegral = 0;
        if (count($lst)) {
            $lockIntegral = -1 * UserBill::getDB()->whereIn('link_id', array_column($lst, 'link_id'))->where('uid', $uid)
                    ->where('category', 'integral')->where('type', 'refund_lock')->sum('number');
        }
        foreach ($lst as $bill) {
            $lockIntegral = bcadd($lockIntegral, $bill['number'], 0);
        }
        return $lockIntegral;
    }

    public function deductionIntegral($uid)
    {
        return UserBill::getDB()->where('uid', $uid)
            ->where('category', 'integral')->where('type', 'deduction')->sum('number');
    }

    public function totalGainIntegral($uid)
    {
        return UserBill::getDB()->where('uid', $uid)
            ->where('category', 'integral')
            ->where('pm', 1)
            ->whereNotIn('type', ['refund', 'cancel','refund_lock'])
            ->where('status', 1)
            ->sum('number');
    }

    /**
     * @param $uid
     * @return float
     * @author xaboy
     * @day 2020/6/22
     */
    public function totalBrokerage($uid)
    {
        return bcsub(UserBill::getDB()->where('category', 'brokerage')
            ->whereIn('type', ['order_one', 'order_two'])->where('uid', $uid)->sum('number'),
            UserBill::getDB()->where('uid', $uid)
                ->where('category', 'brokerage')->whereIn('type', ['refund_two', 'refund_one'])->sum('number'), 2);
    }

    /**
     * @param $uid
     * @return float
     * @author xaboy
     * @day 2020/6/22
     */
    public function yesterdayBrokerage($uid)
    {
        return getModelTime(UserBill::getDB()->where('category', 'brokerage')
            ->whereIn('type', ['order_one', 'order_two'])->where('uid', $uid), 'yesterday')->sum('number');
    }

    /**
     * @param array $where
     * @return \think\db\BaseQuery
     * @author xaboy
     * @day 2020/6/22
     */
    public function search(array $where)
    {
        return UserBill::getDB()
            ->when(isset($where['now_money']) && in_array($where['now_money'], [0, 1, 2]), function ($query) use ($where) {
                if ($where['now_money'] == 0)
                    $query->where('category', 'now_money')->whereIn('type', ['pay_product', 'recharge', 'sys_inc_money', 'sys_dec_money', 'brokerage', 'presell', 'refund']);
                else if ($where['now_money'] == 1)
                    $query->where('category', 'now_money')->whereIn('type', ['pay_product', 'sys_dec_money', 'presell']);
                else if ($where['now_money'] == 2)
                    $query->where('category', 'now_money')->whereIn('type', ['recharge', 'sys_inc_money', 'brokerage', 'refund']);
            })
            ->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
                $query->where('uid', $where['uid'])->where('mer_id', 0);
            })
            ->when(isset($where['pm']) && $where['pm'] !== '', function ($query) use ($where) {
                $query->where('pm', $where['pm']);
            })
            ->when(isset($where['category']) && $where['category'] !== '', function ($query) use ($where) {
                $query->where('category', $where['category']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'create_time');
            })
            ->when(isset($where['day']) && $where['day'] !== '', function ($query) use ($where) {
                $query->whereDay('create_time', $where['day']);
            })
            ->when(isset($where['month']) && $where['month'] !== '', function ($query) use ($where) {
                $query->whereMonth('create_time', $where['month']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $data = explode('/', $where['type'], 2);
                if (count($data) > 1) {
                    $query->where('category', $data[0])->where('type', $data[1]);
                } else {
                    $query->where('type', $where['type']);
                }
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('mer_id', $where['mer_id']);
            })
            ->when(isset($where['link_id']) && $where['link_id'] !== '', function ($query) use ($where) {
                $query->where('link_id', $where['link_id']);
            });
    }

    public function userNowMoneyIncTotal($uid)
    {
        return $this->search(['uid' => $uid, 'now_money' => 2])->sum('number');
    }

    public function searchJoin(array $where)
    {
        return UserBill::getDB()->alias('a')->leftJoin('User b', 'a.uid = b.uid')
            ->field('a.bill_id,a.pm,a.title,a.number,a.balance,a.mark,a.create_time,a.status,b.nickname,a.uid,a.category')
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('a.mer_id', $where['mer_id']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $data = explode('/', $where['type'], 2);
                if (count($data) > 1) {
                    $query->where('a.category', $data[0])->where('type', $data[1]);
                } else {
                    $query->where('a.type', $where['type']);
                }
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'a.create_time');
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->whereLike('a.uid|b.nickname|a.title', "%{$where['keyword']}%");
            })
            ->when(isset($where['category']) && $where['category'] !== '', function ($query) use ($where) {
                $query->where('a.category', $where['category']);
            })->where('category', '<>', 'sys_brokerage');

    }

    public function refundBrokerage($order_id, $uid)
    {
        return UserBill::getDB()->where('link_id', $order_id)->where('uid', $uid)
            ->where('category', 'brokerage')->whereIn('type', ['refund_two', 'refund_one'])->sum('number');
    }

    public function refundIntegral($order_id, $uid)
    {
        return UserBill::getDB()->where('link_id', $order_id)->where('uid', $uid)
            ->where('category', 'integral')->where('type', 'refund_lock')->sum('number');
    }

    public function validIntegral($uid, $start, $end)
    {
        $lst = UserBill::getDB()->where('category', 'integral')
            ->where('type', 'lock')->whereBetween('create_time', [$start, $end])->where('uid', $uid)->where('status', 1)->field('link_id,number')->select()->toArray();
        $integral = 0;
        if (count($lst)) {
            $integral = -1 * UserBill::getDB()->whereIn('link_id', array_column($lst, 'link_id'))->where('uid', $uid)
                    ->where('category', 'integral')->where('type', 'refund_lock')->sum('number');
        }
        foreach ($lst as $bill) {
            $integral = bcadd($integral, $bill['number'], 0);
        }
        $integral2 = UserBill::getDB()->where('uid', $uid)->whereBetween('create_time', [$start, $end])
            ->where('category', 'integral')->where('pm', 1)->whereNotIn('type', ['lock', 'refund'])->sum('number');
        $integral3 = UserBill::getDB()->where('uid', $uid)->whereBetween('create_time', [$start, $end])
            ->where('category', 'integral')->where('type', 'sys_dec')->sum('number');
        return (int)max(bcsub(bcadd($integral, $integral2, 0), $integral3, 0), 0);
    }


}
