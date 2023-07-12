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
use app\common\model\store\order\StoreOrderReceipt;
use app\common\model\user\User;

class StoreOrderReceiptDao extends BaseDao
{
    protected function getModel(): string
    {
        return StoreOrderReceipt::class;
    }

    public function search(array $where)
    {
        if((isset($where['order_type']) && $where['order_type'] !== '') || (isset($where['keyword']) && $where['keyword'] !== '')){
            $query = StoreOrderReceipt::hasWhere('storeOrder',function($query)use($where){
                switch ($where['order_type'])
                {
                    case 1:
                        $query->where('StoreOrder.paid',0)->where('StoreOrder.is_del',0);
                        break;    // 未支付
                    case 2:
                        $query->where('StoreOrder.paid',1)->where('StoreOrder.status',0)->where('StoreOrder.is_del',0);
                        break;  // 待发货
                    case 3:
                        $query->where('StoreOrder.status',1)->where('StoreOrder.is_del',0);
                        break;  // 待收货
                    case 4:
                        $query->where('StoreOrder.status',2)->where('StoreOrder.is_del',0);
                        break;  // 待评价
                    case 5:
                        $query->where('StoreOrder.status',3)->where('StoreOrder.is_del',0);
                        break;  // 交易完成
                    case 6:
                        $query->where('StoreOrder.status',-1)->where('StoreOrder.is_del',0);
                        break; // 已退款
                    case 7:
                        $query->where('StoreOrder.is_del',1);
                        break;  // 已删除
                    case 8:
                        $query->where('StoreOrder.is_del', 0);
                        break;  //全部
                    default:
                        $query->where(true);
                        break;         //全部
                }
                $query->when(isset($where['keyword']) && $where['keyword'] !== '' ,function($query)use($where){
                    $query->whereLike("order_sn|real_name|user_phone","%{$where['keyword']}%");
                });
            });
        }else{
            $query = StoreOrderReceipt::alias('StoreOrderReceipt');
        }
        $query->when(isset($where['status']) && $where['status'] !== '' ,function($query)use($where){
                $query->where('StoreOrderReceipt.status',$where['status']);
            })
            ->when(isset($where['date']) && $where['date'] !== '' ,function($query)use($where){
                getModelTime($query,$where['date'],'StoreOrderReceipt.create_time');
            })
            ->when(isset($where['receipt_sn']) && $where['receipt_sn'] !== '' ,function($query)use($where){
                $query->where('StoreOrderReceipt.receipt_sn',$where['receipt_sn']);
            })
            ->when(isset($where['username']) && $where['username'] !== '' ,function($query)use($where){
                $uid = User::whereLike('nickname|phone',"%{$where['username']}%")->column('uid');
                $query->where('StoreOrderReceipt.uid','in',$uid);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '' ,function($query)use($where){
                $query->where('StoreOrderReceipt.mer_id',$where['mer_id']);
            })
            ->when(isset($where['uid']) && $where['uid'] !== '' ,function($query)use($where){
                $query->where('StoreOrderReceipt.uid',$where['uid']);
            })
        ;
        return $query->order('StoreOrderReceipt.create_time DESC');
    }

    public function updateBySn(string $receipt_sn,$data)
    {
        return $this->getModel()::getDB()->where('receipt_sn',$receipt_sn)->update($data);
    }


    public function deleteByOrderId($id)
    {
        return $this->getModel()::getDB()->where('order_id',$id)->delete();
    }
}
