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


namespace app\common\repositories\store\order;


use app\common\dao\store\order\StoreGroupOrderDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserRepository;
use crmeb\jobs\CancelGroupOrderJob;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Queue;
use think\model\Relation;

/**
 * Class StoreGroupOrderRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/6/8
 * @mixin StoreGroupOrderDao
 */
class StoreGroupOrderRepository extends BaseRepository
{
    /**
     * StoreGroupOrderRepository constructor.
     * @param StoreGroupOrderDao $dao
     */
    public function __construct(StoreGroupOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->search($where,0);
        $count = $query->count();
        $list = $query->with(['orderList' => function (Relation $query) {
            $query->field('order_id,group_order_id,activity_type,pay_price')->with(['orderProduct','presellOrder']);
        }])->page($page, $limit)->order('create_time DESC')->select();
        return compact('count', 'list');
    }

    /**
     * @param $uid
     * @param $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function detail($uid, $id,$flag = true)
    {
        return $this->search(['paid' => 0, 'uid' => $uid])->where('group_order_id', $id)->with(['orderList' => function (Relation $query) use ($flag) {
            $query->when($flag, function ($query) {
                $query->field('order_id,group_order_id,mer_id,order_sn,activity_type,pay_price,order_extend,order_type,is_virtual');
            })->with(['merchant' => function ($query) use ($flag) {
                $flag && $query->field('mer_id,mer_name');
            }, 'orderProduct', 'presellOrder']);
        }])->find();
    }

    public function status($uid, $id)
    {
        return $this->search(['uid' => $uid])->where('group_order_id', $id)->append(['give_coupon'])->find();
    }

    /**
     * @param $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function getCancelDetail($id)
    {
        return $this->search(['paid' => 0, 'is_del' => 1])->where('group_order_id', $id)->with(['orderList.orderProduct'])->find();
    }

    /**
     * @param $id
     * @param null $uid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function cancel($id, $uid = null)
    {
        $groupOrder = $this->search(['paid' => 0, 'uid' => $uid ?? ''])->where('group_order_id', $id)->with(['orderList'])->find();
        if (!$groupOrder)
            throw new ValidateException('订单不存在');
        if ($groupOrder['paid'] != 0)
            throw new ValidateException('订单状态错误,无法删除');
        //TODO 关闭订单
        Db::transaction(function () use ($groupOrder, $id, $uid) {
            $groupOrder->is_del = 1;
            $orderStatus = [];

            //退回积分
            if ($groupOrder->integral > 0) {
                $make = app()->make(UserRepository::class);
                $make->update($groupOrder->uid, ['integral' => Db::raw('integral+' . $groupOrder->integral)]);
                app()->make(UserBillRepository::class)->incBill($groupOrder->uid, 'integral', 'cancel', [
                    'link_id' => $groupOrder['group_order_id'],
                    'status' => 1,
                    'title' => '退回积分',
                    'number' => $groupOrder['integral'],
                    'mark' => '订单自动关闭,退回' . intval($groupOrder->integral) . '积分',
                    'balance' => $make->get($groupOrder->uid)->integral
                ]);
            }
            //订单记录
            $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
            foreach ($groupOrder->orderList as $order) {
                if ($order->activity_type == 3 && $order->presellOrder) {
                    $order->presellOrder->status = 0;
                    $order->presellOrder->save();
                }
                $order->is_del = 1;
                $order->save();
                $orderStatus[] = [
                    'order_id' => $order->order_id,
                    'order_sn' => $order->order_sn,
                    'type' => $storeOrderStatusRepository::TYPE_ORDER,
                    'change_message' => '取消订单',
                    'change_type' => $storeOrderStatusRepository::ORDER_STATUS_CANCEL,
                    'uid' => $uid ?:0 ,
                    'nickname' => $uid ? $order->user->nickname : '系统',
                    'user_type' => $storeOrderStatusRepository::U_TYPE_SYSTEM,
                ];
            }
            $groupOrder->save();
            $storeOrderStatusRepository->batchCreateLog($orderStatus);
        });
        Queue::push(CancelGroupOrderJob::class, $id);
    }

    public function isVipCoupon($groupOrder)
    {
        if (!$groupOrder->coupon_id) {
            return false;
        }
        $cid = app()->make(StoreCouponUserRepository::class)->query(['coupon_user_id' => $groupOrder->coupon_id])->value('coupon_id');
        if ($cid) {
            return app()->make(StoreCouponRepository::class)->query(['coupon_id' => $cid])->value('send_type') === StoreCouponRepository::GET_COUPON_TYPE_SVIP;
        }
        return false;
    }
}
