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

use app\common\model\store\order\MerchantReconciliationOrder;
use app\common\model\system\merchant\Merchant;
use app\common\repositories\BaseRepository;
use app\common\dao\store\order\MerchantReconciliationDao as dao;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\order\StoreRefundOrderRepository;
use app\common\repositories\system\admin\AdminRepository;
use app\common\repositories\system\merchant\FinancialRecordRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use crmeb\services\SwooleTaskService;
use think\exception\ValidateException;
use think\facade\Db;
use FormBuilder\Factory\Elm;
use think\facade\Route;

class MerchantReconciliationRepository extends BaseRepository
{
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }


    public function getWhereCountById($id)
    {
        $where = ['reconciliation_id' => $id,'status' => 2];
        return $this->dao->getWhereCount($where) > 0 ;
    }

    public function merWhereCountById($id,$merId)
    {
        $where = ['reconciliation_id' => $id,'mer_id' => $merId,'is_accounts' => 0,'status' => 0];
        return ($this->dao->getWhereCount($where) > 0) ;
    }

    /**
     * TODO 列表
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-15
     */
    public function getList($where,$page,$limit)
    {
        $query = $this->dao->search($where)->with([
            'merchant' => function($query){
                $query->field('mer_id,mer_name');
            },
            'admin' =>function($query){
                $query->field('admin_id,real_name');
            }]);
        $count = $query->count();
        $list = $query->page($page,$limit)->select()->append(['charge'])->each(function($item){
            if($item->type == 1) return $item->price = '-'.$item->price;
        });

        return compact('count','list');
    }


    /**
     * TODO 创建对账单
     * @param $id
     * @param $data
     * @author Qinii
     * @day 2020-06-15
     */
    public function create(int $id, array $data)
    {
        $orderMake = app()->make(StoreOrderRepository::class);
        $refundMake = app()->make(StoreRefundOrderRepository::class);

        $bank = merchantConfig($id,'bank');
        $bank_name = merchantConfig($id,'bank_name');
        $bank_number = merchantConfig($id,'bank_number');
        $bank_address = merchantConfig($id,'bank_address');
        if( !$bank || !$bank_name || !$bank_number || !$bank_address )
            throw  new ValidateException('商户未填写银行卡信息');

        $order_ids = $data['order_ids'];
        $refund_order_ids = $data['refund_order_ids'];
        if($data['order_type']) { //全选
            $order_ids = $orderMake->search([
                'date' => $data['date'],
                'mer_id' => $id,
                'paid' => 1,
                'order_id'
            ], null
            )->whereNotIn('order_id', $data['order_out_ids'])->column('order_id');
        }
        if($data['refund_type']){ //全选
            $refund_order_ids = $refundMake->search([
                'date' => $data['date'],
                'mer_id' => $id,
                'reconciliation_type' => 0,
                'status' => 3
            ])->whereNotIn('refund_order_id',$data['refund_out_ids'])->column('refund_order_id');
        }
        if(is_array($order_ids) && (count($order_ids) < 1) && is_array($refund_order_ids) && (count($refund_order_ids) < 1)){
            throw new ValidateException('没有数据可对账');
        }
        $compute = $this->compute($id,$order_ids,$refund_order_ids);
        $createData = [
            'status'        => 0,
            'mer_id'        => $id,
            'is_accounts'   => 0,
            'mer_admin_id'  => 0,
            'bank'          => $bank,
            'bank_name'     => $bank_name,
            'bank_number'   => $bank_number,
            'bank_address'  => $bank_address,
            'admin_id'      => $data['adminId'],
            'price'         => round($compute['price'],2),
            'order_price'   => round($compute['order_price'],2),
            'refund_price'  => round($compute['refund_price'],2),
            //'refund_rate'   => round($compute['refund_rate'],2),
            'order_rate'    => round($compute['order_rate'],2),
            'order_extension'  =>  round($compute['order_extension'],2),
            'refund_extension' =>  round($compute['refund_extension'],2),
        ];

        Db::transaction(function()use($order_ids,$refund_order_ids,$orderMake,$refundMake,$createData){
            $res = $this->dao->create($createData);
            $orderMake->updates($order_ids,['reconciliation_id' => $res['reconciliation_id']]);
            $refundMake->updates($refund_order_ids,['reconciliation_id' => $res['reconciliation_id']]);
            $this->reconciliationOrder($order_ids,$refund_order_ids,$res['reconciliation_id']);

            SwooleTaskService::merchant('notice', [
                'type' => 'accoubts',
                'data'=>[
                    'title' => '新对账',
                    'message' => '您有一条新的对账单',
                    'id' => $res['reconciliation_id']
                ]
            ], $createData['mer_id']);
        });
    }

    /**
     * TODO 计算对账单金额
     * @param $merId
     * @param $order_ids
     * @param $refund_order_ids
     * @return array
     * @author Qinii
     * @day 2020-06-23
     */
    public function  compute($merId,$order_ids,$refund_order_ids)
    {
        $order_price = $refund_price = $order_extension = $refund_extension = $order_rate = $refund_rate =0;
        $orderMake = app()->make(StoreOrderRepository::class);
        $refundMake = app()->make(StoreRefundOrderRepository::class);

        foreach($order_ids as $item){
            if(!$order = $orderMake->getWhere(['order_id' => $item,'mer_id' => $merId,'paid' => 1]))
                throw new ValidateException('订单信息不存在或状态错误');

            if($order['reconciliation_id']) throw new ValidateException('订单重复提交');

            //(实付金额 - 一级佣金 - 二级佣金) * 抽成
            $commission_rate = ($order['commission_rate'] / 100);
            //佣金
            $_order_extension = bcadd($order['extension_one'],$order['extension_two'],3);
            $order_extension = bcadd($order_extension,$_order_extension,3);

            //手续费 =  (实付金额 - 一级佣金 - 二级佣金) * 比例
            $_order_rate = bcmul(bcsub($order['pay_price'],$_order_extension,3),$commission_rate,3);
            $order_rate = bcadd($order_rate,$_order_rate,3);

            //金额
            $_order_price = bcsub(bcsub($order['pay_price'],$_order_extension,3),$_order_rate,3);
            $order_price = bcadd($order_price,$_order_price,3);
        }

        foreach($refund_order_ids as $item){
            if(!$refundOrder = $refundMake->getWhere(['refund_order_id' => $item,'mer_id' => $merId,'status' => 3],'*',['order']))
                throw new ValidateException('退款订单信息不存在或状态错误');
            if($refundOrder['reconciliation_id']) throw new ValidateException('退款订单重复提交');

            //退款金额 + 一级佣金 + 二级佣金
            $refund_commission_rate = ($refundOrder['order']['commission_rate'] / 100);
            //佣金
            $_refund_extension = bcadd($refundOrder['extension_one'],$refundOrder['extension_two'],3);
            $refund_extension = bcadd($refund_extension,$_refund_extension,3);

            //手续费
//            $_refund_rate = bcmul(bcsub($refundOrder['refund_price'],$_refund_extension,3),$refund_commission_rate,3);
//            $refund_rate = bcadd($refund_rate,$_refund_rate,3);

            //金额
            $_refund_price = bcadd($refundOrder['refund_price'],$_refund_extension,3);
            $refund_price = bcadd($refund_price,$_refund_price,3);
        }

        $price = bcsub($order_price,$refund_price,3);

        return compact('price','refund_price','order_extension','refund_extension','order_price','order_rate');
    }

    /**
     * TODO
     * @param $order_ids
     * @param $refund_ids
     * @param $reconciliation_id
     * @return mixed
     * @author Qinii
     * @day 2020-06-23
     */
    public function reconciliationOrder($order_ids,$refund_ids,$reconciliation_id)
    {
        $data = [];
        foreach ($order_ids as $item){
            $data[] = [
                'order_id' => $item,
                'reconciliation_id' => $reconciliation_id,
                'type' => 0,
            ];
        }
        foreach ($refund_ids as $item) {
            $data[] = [
                'order_id' => $item,
                'reconciliation_id' => $reconciliation_id,
                'type' => 1,
            ];
        }
        return app()->make(MerchantReconciliationOrderRepository::class)->insertAll($data);
    }

    /**
     * TODO 修改状态
     * @param $id
     * @param $data
     * @param $type
     * @author Qinii
     * @day 2020-06-15
     */
    public function switchStatus($id,$data)
    {
        Db::transaction(function()use($id,$data){
            if(isset($data['status']) && $data['status'] == 1){
                app()->make(StoreRefundOrderRepository::class)->reconciliationUpdate($id);
                app()->make(StoreOrderRepository::class)->reconciliationUpdate($id);
            }
            $this->dao->update($id,$data);
        });
        $res = $this->dao->get($id);
        $mer = app()->make(MerchantRepository::class)->get($res['mer_id']);
        if(isset($data['is_accounts']) && $data['is_accounts']){
//            $make = app()->make(FinancialRecordRepository::class);
//
//            $make->dec([
//                'order_id' => $id,
//                'order_sn' => $id,
//                'user_info' => $mer['mer_name'],
//                'user_id' => $res['mer_id'],
//                'financial_type' => 'sys_accoubts',
//                'number' => $res->price,
//            ],0);
//
//            $make->inc([
//                'order_id' => $id,
//                'order_sn' => $id,
//                'user_info' => '总平台',
//                'user_id' => 0,
//                'financial_type' => 'mer_accoubts',
//                'number' => $res->price,
//            ],$res->mer_id);

            SwooleTaskService::merchant('notice', [
                'type' => 'accoubts',
                'data'=>[
                    'title' => '新对账打款',
                    'message' => '您有一条新对账打款通知',
                    'id' => $id
                ]
            ], $res['mer_id']);
        }
    }

    public function markForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('merchantReconciliationMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('mark', '备注',$data['mark'])->required(),
        ]);
        return $form->setTitle('修改备注');
    }

    public function adminMarkForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('systemMerchantReconciliationMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('admin_mark', '备注',$data['admin_mark'])->required(),
        ]);
        return $form->setTitle('修改备注');
    }
}
