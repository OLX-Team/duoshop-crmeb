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
use app\common\model\store\order\StoreOrder;
use app\common\model\store\order\StoreRefundOrder;
use app\common\repositories\system\merchant\MerchantRepository;
use think\db\BaseQuery;
use think\db\exception\DbException;

class StoreRefundOrderDao extends BaseDao
{

    protected function getModel(): string
    {
        return StoreRefundOrder::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/12
     */
    public function search(array $where)
    {
        if(isset($where['is_trader']) && $where['is_trader'] !== ''){
            $query = StoreRefundOrder::hasWhere('merchant',function($query)use($where){
                $query->where('is_trader',$where['is_trader']);
            });
        }else{
            $query = (StoreRefundOrder::getDB())->alias('StoreRefundOrder');
        }
        $query->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('StoreRefundOrder.mer_id', $where['mer_id']);
        })->when(isset($where['order_sn']) && $where['order_sn'] !== '', function ($query) use ($where) {
            $ids = StoreOrder::where('order_sn','like','%'.$where['order_sn'].'%')->column('order_id');
            $query->where('order_id','in',$ids);
        })->when(isset($where['refund_order_sn']) && $where['refund_order_sn'] !== '', function ($query) use ($where) {
            $query->where('refund_order_sn', 'like', '%' . $where['refund_order_sn'] . '%');
        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('StoreRefundOrder.status', $where['status']);
        })->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
            $query->where('refund_order_id', $where['id']);
        })->when(isset($where['is_del']) && $where['is_del'] !== '', function ($query) use ($where) {
            $query->where('StoreRefundOrder.is_del', $where['is_del']);
        })->when(isset($where['type']) && $where['type'] == 1, function ($query) {
            $query->whereIn('StoreRefundOrder.status', [0, 1, 2]);
        })->when(isset($where['type']) && $where['type'] == 2, function ($query) {
            $query->whereIn('status', [-1, 3,-10]);
        })->when(isset($where['refund_type']) && $where['refund_type'] !== '',function($query)use($where){
            $query->where('refund_type',$where['refund_type']);
        })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query,$where['date'],'StoreRefundOrder.create_time');
        })->when(isset($where['order_id']) && $where['order_id'] !== '', function ($query) use ($where) {
            $query->where('order_id', $where['order_id']);
        })->when(isset($where['delivery_id']) && $where['delivery_id'] !== '', function ($query) use ($where) {
            $query->where('StoreRefundOrder.delivery_id', $where['delivery_id']);
        })->order('StoreRefundOrder.create_time DESC');
        return $query;
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-12
     */
    public function getOne($id)
    {
        return $this->getModel()::where($this->getPk(),$id)->with([
            'refundProduct.product',
            'user' => function($query){
                $query->field('uid,nickname,phone');
            },
            'order.orderProduct'
            ])->find();
    }

    /**
     * @param $where
     * @return bool
     * @author Qinii
     * @day 2020-06-12
     */
    public function getFieldExists($where)
    {
        return (($this->getModel()::getDB())->where($where)->count()) > 0;
    }

    /**
     * @param $uid
     * @param $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020/6/12
     */
    public function userDel($uid, $id)
    {
        return StoreRefundOrder::getDB()->where('uid', $uid)->where('refund_order_id', $id)->where('status', 3)->update(['is_del' => 1, 'status_time' => date('Y-m-d H:i:s')]);
    }

    /**
     * TODO超过期限退款申请
     * @param $time
     * @return mixed
     * @author Qinii
     * @day 2020-06-13
     */
    public function getTimeOutIds($time)
    {
        return ($this->getModel()::getDB())->where('status_time','<=',$time)
            ->where(function($query){
                $query->where(function($query){
                    $query->where('refund_type',1)->where('status',0);
                })->whereOr(function($query){
                    $query->where('refund_type',2)->where('status',2);
                });
            })->column('refund_order_id');
    }

    /**
     * TODO
     * @param $reconciliation_id
     * @return mixed
     * @author Qinii
     * @day 2020-06-15
     */
    public function reconciliationUpdate($reconciliation_id)
    {
        return ($this->getModel()::getDB())->whereIn('reconciliation_id',$reconciliation_id)->update(['reconciliation_id' => 0]);
    }

    public function refundPirceByOrder(array $orderIds)
    {
        return $this->getModel()::getDB()->whereIn('order_id',$orderIds)->where('status',3)->sum('refund_price');
    }

}
