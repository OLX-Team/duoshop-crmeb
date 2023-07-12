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

use app\common\repositories\BaseRepository;
use app\common\dao\store\order\StoreOrderReceiptDao;
use app\common\model\store\order\StoreOrder;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\exception\ValidateException;
use think\facade\Route;

/**
 * @mixin StoreOrderReceiptDao
 */
class   StoreOrderReceiptRepository extends BaseRepository
{
    public function __construct(StoreOrderReceiptDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 生成信息
     * @param array $receiptData
     * @param StoreOrder $orderData
     * @param null $orderPrice
     * @author Qinii
     * @day 2020-10-16
     */
    public function add(array $receiptData,StoreOrder $orderData, $orderPrice = null)
    {
        if($this->dao->getWhereCount(['order_id' => $orderData->order_id]))
            throw new ValidateException('该订单已存在发票信息');

        if (!$receiptData['receipt_type'] ||
            !$receiptData['receipt_title_type'] ||
            !$receiptData['receipt_title']
        ) throw new ValidateException('发票信息不全');

        if($receiptData['receipt_type'] == 1){
            $receipt_info = [
                'receipt_type' => $receiptData['receipt_type'],
                'receipt_title_type' => $receiptData['receipt_title_type'],
                'receipt_title' => $receiptData['receipt_title'],
                'duty_paragraph' => $receiptData['duty_paragraph']
            ];
            $delivery_info = [
                'email' => $receiptData['email']
            ];
        }
        if($receiptData['receipt_type'] == 2){
            if (
                !$receiptData['duty_paragraph'] ||
                !$receiptData['bank_name'] ||
                !$receiptData['bank_code'] ||
                !$receiptData['address']  ||
                !$receiptData['tel']
            ) throw new ValidateException('发票信息不全');
            $receipt_info = [
                'receipt_type' => $receiptData['receipt_type'],
                'receipt_title_type' => $receiptData['receipt_title_type'],
                'receipt_title' => $receiptData['receipt_title'],
                'duty_paragraph' => $receiptData['duty_paragraph'],
                'bank_name' => $receiptData['bank_name'],
                'bank_code' => $receiptData['bank_code'],
                'address' => $receiptData['address'],
                'tel' => $receiptData['tel'],
            ];
            $delivery_info = [
                'user_name' => $orderData['real_name'],
                'user_phone' => $orderData['user_phone'],
                'user_address' => $orderData['user_address'],
            ];
        }
        $data = [
            'order_id' => $orderData->order_id,
            'uid' => $orderData->uid,
            'mark' => $receiptData['mark'] ?? '',
            'order_price' => $orderPrice ?? $orderData['pay_price'],
            'receipt_info' => json_encode($receipt_info),
            'delivery_info'=> json_encode($delivery_info),
            'status_time' => date('Y-m-d H:i:s',time()),
            'mer_id' => $orderData->mer_id
        ];
        $this->dao->create($data);
    }

    /**
     * TODO 列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-10-17
     */
    public function getList(array $where,int $page,int $limit)
    {
        $query = $this->dao->search($where)->with([
            'storeOrder' => function ($query) {
                $query->field('order_id,order_sn,real_name,user_phone,user_address,status,paid,is_del,pay_price,paid,group_order_id,mark');
            },
            'user' => function ($query) {
                $query->field('uid,nickname,phone');
            },
            'merchant'  => function ($query) {
                $query->field('mer_id,mer_name');
            },]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }

    /**
     * TODO 开票
     * @param string $ids
     * @author Qinii
     * @day 2020-10-17
     */
    public function setRecipt(string $ids,int $merId)
    {
        $data = $this->dao->getSearch(['order_receipt_ids' => $ids,'mer_id' => $merId])->order('create_time Desc')->select();
        $arr = $this->check($ids);
        $receipt_price = 0;
        foreach ($data as $item){
            if($item['status'] == 1) throw new ValidateException('存在已开票订单ID：'.$item['order_receipt_id']);
            $receipt_price = bcadd($receipt_price,$item['order_price'],2);
            $delivery_info = $item['delivery_info'];
        }
        $receipt_info = json_decode($arr[0]);
        if($receipt_info->receipt_type == 1 ){
            $title = $receipt_info->receipt_title_type == 1 ? '个人电子普通发票' : '企业电子普通发票';
        }else{
            $title = '企业专用纸质发票';
        }
        return $res = [
            "title" => $title,
            "receipt_sn" => $this->receiptSn(),
            "receipt_price" =>  $receipt_price,
            'receipt_info' => $receipt_info,
            'delivery_info' => $delivery_info,
            'status' => 0,
        ];
    }

    public function merExists(string $ids,int $merId)
    {
        $ids = explode(',',$ids);
        foreach ($ids as $id) {
            if(!$this->dao->getSearch(['order_receipt_id' => $id,'mer_id' => $merId])->count())
                throw new ValidateException('数据有误,存在不属于您的发票ID');
        }
        return true;
    }

    /**
     * TODO 保存合并的发票信息
     * @param array $data
     * @author Qinii
     * @day 2020-12-02
     */
    public function save(array $data)
    {
        $this->check($data['ids']);
        $res = [
            "receipt_sn" => $data['receipt_sn'],
            "receipt_price" =>  $data['receipt_price'],
            'status'    => $data['receipt_no'] ? 1 : 2,
            'status_time' => date('Y-m-d H:i:s',time()),
            'receipt_no' => $data['receipt_no'],
            'mer_mark' => $data['mer_mark']
        ];
       $this->dao->updates(explode(',',$data['ids']),$res);
    }
    public function check(string $ids)
    {
        $query = $this->dao->getSearch(['order_receipt_ids' => $ids])->with(['storeOrder' => function($query){$query->field('order_id,paid');}]);
        $result = $query->select();
        foreach ($result as $item){
            if(!$item->storeOrder['paid']) throw new ValidateException('订单未支付不可开发票');
        }
        $data = $query->column('receipt_info');
        $arr = array_unique($data);
        if(count($arr) > 1) throw new ValidateException('开票信息不相同，无法合并');
        return $arr;
    }

    /**
     * TODO 生成发票号
     * @return string
     * @author Qinii
     * @day 2020-10-17
     */
    public function receiptSn()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $orderId = 'PT' . $msectime . mt_rand(10000, max(intval($msec * 10000) + 10000, 98369));
        return $orderId;
    }

    public function markForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('merchantOrderReceiptMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('mer_mark', '备注', $data['mer_mark'])->required(),
        ]);
        return $form->setTitle('修改备注');
    }
}
