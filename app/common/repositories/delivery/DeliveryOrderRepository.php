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
namespace app\common\repositories\delivery;

use app\common\dao\delivery\DeliveryOrderDao;
use app\common\model\delivery\DeliveryStation;
use app\common\model\store\order\StoreOrder;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\order\StoreOrderStatusRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use app\common\repositories\user\UserRepository;
use crmeb\services\DeliverySevices;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Log;
use think\facade\Route;

class DeliveryOrderRepository extends BaseRepository
{

    protected  $statusData  = [
        2   => '待取货',
        3   => '配送中',
        4   => '已完成',
        -1  => '已取消',
        9   => '物品返回中',
        10  => '物品返回完成',
        100 => '骑士到店',
    ];

    protected $message = [
        2   => StoreOrderStatusRepository::ORDER_DELIVERY_CITY_WAITING,
        3   => StoreOrderStatusRepository::ORDER_DELIVERY_CITY_ING,
        4   => StoreOrderStatusRepository::ORDER_DELIVERY_CITY_OVER,
        -1  => StoreOrderStatusRepository::ORDER_DELIVERY_CITY_CANCEL,
        9   => StoreOrderStatusRepository::ORDER_DELIVERY_CITY_REFUNDING,
        10  => StoreOrderStatusRepository::ORDER_DELIVERY_CITY_REFUND,
        100 => StoreOrderStatusRepository::ORDER_DELIVERY_CITY_ARRIVE,
    ];

    public function __construct(DeliveryOrderDao $dao)
    {
        $this->dao = $dao;
    }

    public function merList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->with(['station','storeOrder'])->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }

    public function sysList(array $where, int $page, int $limit)
    {
        $query = $this->dao->getSearch($where)->with([
            'merchant' => function($query) {
                $query->field('mer_id,mer_name');
            },
            'station',
            'storeOrder'=> function($query) {
                $query->field('order_id,order_sn');
            },
        ])->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }

    public function detail(int $id, ?int $merId)
    {
        $where[$this->dao->getPk()] = $id;
        if ($merId) $where['mer_id'] = $merId;
        $res = $this->dao->getSearch($where)->with([
            'merchant' => function($query) {
                $query->field('mer_id,mer_name');
            },
            'station',
        ])->find();
        $order = DeliverySevices::create($res['station_type'])->getOrderDetail($res);
        $res['data'] = [
            'order_code' => $order['order_code'],
            'to_address' => $order['to_address'],
            'from_address' => $order['from_address'],
            'state' => $order['state'],
            'note' => $order['note'],
            'order_price' => $order['order_price'],
            'distance' => round(($order['distance'] / 1000),2) . ' km',
        ];
        if (!$res) throw new ValidateException('订单不存在');
        return $res;
    }

    public function cancelForm($id)
    {
        $formData = $this->dao->get($id);
        if (!$formData) throw new ValidateException('订单不存在');
        if ($formData['status'] == -1) throw new ValidateException('订单已取消，无法操作');

        $form = Elm::createForm(Route::buildUrl('merchantStoreDeliveryOrderCancel',['id' => $id])->build());
        $rule = [];
        if ($formData['station_type'] == DeliverySevices::DELIVERY_TYPE_DADA){
            $options = DeliverySevices::create(DeliverySevices::DELIVERY_TYPE_DADA)->reasons();
            $rule[] = Elm::select('reason', '取消原因')->options($options);
            $rule[] = Elm::text('cancel_reason', '其他原因说明');
        }
        if ($formData['station_type'] == DeliverySevices::DELIVERY_TYPE_UU){
            $rule[] =  Elm::input('reason', '取消原因')->required(1);
        }
        $form->setRule($rule);
        return $form->setTitle('取消同城配送订单',$formData);
    }

    public function cancel($id, $merId, $reason)
    {
        $order = $this->dao->getWhere([$this->dao->getPk() => $id, 'mer_id' => $merId]);
        if (!$order) throw new ValidateException('配送订单不存在');
        if ($order['status'] == -1) throw new ValidateException('请勿重复操作');
        $data = [
            'origin_id' => $order['order_sn'],
            'order_code'=> $order['order_code'],
            'reason'    => $reason['reason'],
            'cancel_reason' => $reason['cancel_reason'],
        ];
        return Db::transaction(function () use($order, $data){
            $mark = $data['reason'];
            if ($order['station_type'] == DeliverySevices::DELIVERY_TYPE_DADA) {
                $options = DeliverySevices::create(DeliverySevices::DELIVERY_TYPE_DADA)->reasons();
                $mark = $options[$data['reason']];
            }
            if ($data['cancel_reason']) $mark .= ','.$data['cancel_reason'];
            $res = DeliverySevices::create($order['station_type'])->cancelOrder($data);
            $deduct_fee = $res['deduct_fee'] ?? 0;
            $this->cancelAfter($order, $deduct_fee, $mark);
            //订单记录
            $statusRepository = app()->make(StoreOrderStatusRepository::class);
            $orderStatus = [
                'order_id' => $order->order_id,
                'order_sn' => $order->order_sn,
                'type' => $statusRepository::TYPE_ORDER,
                'change_message' => '同城配送订单已取消',
                'change_type' => $statusRepository::ORDER_DELIVERY_CITY_CANCEL,
            ];
            $statusRepository->createAdminLog($orderStatus);
        });
    }

    public function cancelAfter($deliveryOrder, $deductFee, $mark)
    {
        //修改配送订单
        $deliveryOrder->status = -1;
        $deliveryOrder->reason = $mark;
        $deliveryOrder->deduct_fee = $deductFee;
        $deliveryOrder->save();

        //修改商城订单
        $res = app()->make(StoreOrderRepository::class)->get($deliveryOrder['order_id']);
        $res->status = 0;
        $res->delivery_type = 0;
        $res->delivery_name = '';
        $res->delivery_id = '';
        $res->save();

        //修改商户
        $merchant = app()->make(MerchantRepository::class)->get($deliveryOrder['mer_id']);
        $balance = bcadd(bcsub($deliveryOrder['fee'], $deductFee, 2), $merchant->delivery_balance, 2);
        $merchant->delivery_balance = $balance;
        $merchant->save();
    }




    /**
     * TODO 回调
     * @param $data
     * @author Qinii
     * @day 2/17/22
     */
    public function notify($data)
    {
        //达达
        /**
         * 订单状态(待接单＝1,待取货＝2,配送中＝3,已完成＝4,已取消＝5, 指派单=8,妥投异常之物品返回中=9, 妥投异常之物品返回完成=10, 骑士到店=100,创建达达运单失败=1000 可参考文末的状态说明）
         */
        Log::info('同城回调参数：'.var_export(['=======',$data,'======='],1));
        if (isset($data['data'])) {
            $data  = json_decode($data['data'], 1);
        }

        $reason = '';
        $deductFee = 0;
        $delivery = [];
        if (isset($data['order_status'])){
            $order_sn = $data['order_id'];
            if ($data['order_status'] == 1) {
                $orderData = $this->dao->getSearch(['sn' => $data['order_id']])->find();
                if (!$orderData['finish_code']) {
                    $orderData->finish_code = $data['finish_code'];
                    $orderData->save();
                }
                return ;
            } else if (in_array( $data['order_status'],[2,3,4,5,9,10,100])){
                $status =  $data['order_status'];
                if ($data['order_status'] == 5){
                    $msg = [
                        '取消：',
                        '达达配送员取消：',
                        '商家主动取消：',
                        '系统或客服取消：',
                    ];
                    //1:达达配送员取消；2:商家主动取消；3:系统或客服取消；0:默认值
                    $status = -1;
                    $reason = $msg[$data['cancel_from']].$data['cancel_reason'];
                }
                $deductFee = $data['deductFee'] ?? 0;
                if (isset($data['dm_name']) && $data['dm_name']) {
                    $delivery = [
                        'delivery_name' => $data['dm_name'],
                        'delivery_id'  => $data['dm_mobile'],
                    ];
                }

            }
        } else if (isset($data['state'])){  //uu
            if (!$data['origin_id']) $deliveryOrder = $this->dao->getWhere(['order_code' => $data['order_code']]);
            $order_sn = $data['origin_id'] ?: $deliveryOrder['order_sn'] ;
            //当前状态 1下单成功 3跑男抢单 4已到达 5已取件 6到达目的地 10收件人已收货 -1订单取消
            switch ($data['state']) {
                case 3:
                    $status = 2;
                    break;
                case 4:
                    $status = 100;
                    break;
                case 5:
                    $status = 3;
                    break;
                case 10:
                    $status = 4;
                    break;
                case -1:
                    $status = -1;
                    $reason = $data['state_text'];
                    break;
                default:
                    break;
            }
            if (isset($data['driver_name']) && $data['driver_name']) {
                $delivery = [
                    'delivery_name' => $data['driver_name'],
                    'delivery_id'  => $data['driver_mobile'],
                ];
            }
        }

        if (isset($order_sn) && isset($status)){
            $res = $this->dao->getWhere(['order_sn' => $order_sn]);
            if ($res) {
                $this->notifyAfter($status, $reason, $res, $delivery, $deductFee);
            }else {
                Log::info('同城配送回调，未查询到订单：'.$order_sn);
            }
        }
    }




    public function notifyAfter($status, $reason, $res, $data, $deductFee)
    {
        if (!isset($this->statusData[$status])) return ;

        $make = app()->make(StoreOrderRepository::class);
        $orderData = $make->get($res['order_id']);

        if ($orderData['status'] != $status ) {
            $res->status = $status;
            $res->reason = $reason;
            $res->save();
            //订单记录
            $statusRepository = app()->make(StoreOrderStatusRepository::class);
            $message = '订单同城配送【'. $this->statusData[$status].'】';
            $orderStatus = [
                'order_id' => $orderData['order_id'],
                'order_sn' => $orderData['order_sn'],
                'type' => $statusRepository::TYPE_ORDER,
                'change_message' => $message,
                'change_type' => $this->message[$status],
            ];
            $statusRepository->createSysLog($orderStatus);
            if ($status == 2 && !empty($data))
                $make->update($res['order_id'],$data);
            if ($status == 4){
                $order = $make->get($res['order_id']);
                $user = app()->make(UserRepository::class)->get($order['uid']);
                $make->update($res['order_id'],['status' => 2]);
                $make->takeAfter($order, $user);
            }
            if ($status == -1)
                $this->cancelAfter($res, $deductFee , $reason);
        }
    }



    public function create($id, $merId, $data, $order)
    {
        $type = systemConfig('delivery_type');
        $callback_url = rtrim(systemConfig('site_url'), '/') . '/api/notice/callback';
        $where = ['station_id' => $data['station_id'], 'mer_id' => $merId, 'status' => 1, 'type' => $type];
        $station = app()->make(DeliveryStationRepository::class)->getWhere($where);

        if (!$station) throw new ValidateException('门店信息不存在');
        if (!$station['city_name']) throw new ValidateException('门店缺少所在城市，请重新编辑门店信息');
        //地址转经纬度
        try{
            $addres = lbs_address($station['city_name'], $order['user_address']);
            if($type == DeliverySevices::DELIVERY_TYPE_UU) {
                [$location['lng'],$location['lat']] = gcj02ToBd09($addres['location']['lng'],$addres['location']['lat']);
            } else {
                $location = $addres['location'];
            }
        }catch (\Exception $e) {
            throw new ValidateException('获取经纬度失败');
        }

        $getPriceParams = $this->getPriceParams($station, $order,$location,$type);
        $orderSn = $this->getOrderSn();
        $getPriceParams['origin_id'] = $orderSn;
        $getPriceParams['callback_url'] = $callback_url;
        $getPriceParams['cargo_weight'] = $data['cargo_weight'] ?? 0;

        $service = DeliverySevices::create($type);
        //计算价格
        $priceData = $service->getOrderPrice($getPriceParams);
        if ($type == DeliverySevices::DELIVERY_TYPE_UU) { //uu
            $priceData['receiver'] = $order['real_name'];
            $priceData['receiver_phone'] = $order['user_phone'];
            $priceData['note'] = $data['mark'];
            $priceData['callback_url'] = $callback_url;
            $priceData['push_type'] = 2;
            $priceData['special_type'] = $data['special_type'] ?? 0;
        }
        app()->make(MerchantRepository::class)->changeDeliveryBalance($merId, $priceData['fee'] ?? $priceData['need_paymoney']);
        //发布订单
        Db::startTrans();
        try{
            $res = $service->addOrder($priceData);
            $ret = [
                'station_id' => $data['station_id'],
                'order_sn' => $orderSn,
                'city_code' => $station['city_name'],
                'receiver_phone' => $order['user_phone'],
                'user_name' => $order['real_name'],
                'from_address' => $station['station_address'],
                'to_address' => $order['user_address'],
                'order_code' => $type == 2 ? $res['ordercode'] : $priceData['deliveryNo'],
                'order_id' => $id,
                'mer_id' => $merId,
                'info' => $data['mark'],
                'status' => $res['status'] ?? 0,
                'station_type' => $type,
                'to_lat' => $addres['location']['lat'],
                'to_lng' => $addres['location']['lng'],
                'from_lat' => $station['lat'],
                'from_lng' => $station['lng'],
                'distance' => $priceData['distance'],
                'fee' => $priceData['fee'] ?? $priceData['need_paymoney'],
                'mark' => $data['mark'],
                'uid' => $order['uid'],
            ];
            //入库操作
            $this->dao->create($ret);
            Db::commit();
            return true;
        }catch (\Exception $exception) {
            if (isset($res['status']) && $res['status']  == 'success'){
                $error['origin_id'] = $orderSn;
                $error['reason'] = $type == 1 ? 36 : '信息错误';
                $error['order_code'] = $type == 2 ? $res['ordercode'] : $priceData['deliveryNo'];
                sleep(1);
                $service->cancelOrder($error);
            }
            Db::rollback();
            throw new ValidateException($exception->getMessage());
        }

    }

    public function getPriceParams(DeliveryStation $deliveryStation, StoreOrder $order, array $addres, int $type)
    {
        $data = [];
        $type = (int)$type;
        switch ($type) {
            case 1:
                $city = DeliverySevices::create(DeliverySevices::DELIVERY_TYPE_DADA)->getCity([]);
                $res = [];
                foreach ($city as $item) {
                    $res[$item['label']] = $item['key'];
                }
                $data = [
                    'shop_no'           => $deliveryStation['origin_shop_id'],
                    'city_code'         => $res[$deliveryStation['city_name']],
                    'cargo_price'       => $order['pay_price'],
                    'is_prepay'         => 0,
                    'receiver_name'     => $order['real_name'],
                    'receiver_address'  => $order['user_address'],
                    'cargo_weight'      => 0,
                    'receiver_phone'    => $order['user_phone'],
                    'is_finish_code_needed' => 1,
                ];
                break;
            case 2:
                $data = [
                    'from_address'      => $deliveryStation['station_address'],
                    'to_address'        => $order['user_address'],
                    'city_name'         => $deliveryStation['city_name'],
                    'goods_type'        => $deliveryStation['business']['label'],
                    'send_type'         =>'0',
                    'to_lat'            => $addres['lat'],
                    'to_lng'            => $addres['lng'],
                    'from_lat'          => $deliveryStation['lat'],
                    'from_lng'          => $deliveryStation['lng'],
                ];
                break;
        }
        return $data;
    }

    public function getTitle()
    {
        $query  = app()->make(MerchantRepository::class)->getSearch(['is_del' => 0]);
        $merchant = $query->count();
        $price = app()->make(ServeOrderRepository::class)
            ->getSearch(['type' => 10,'status' => 1])->sum('pay_price');
        $balance = $query->sum('delivery_balance');
        return [
            [
                'className' => 'el-icon-s-order',
                'count' => $merchant,
                'field' => '个',
                'name' => '商户数'
            ],
            [
                'className' => 'el-icon-s-order',
                'count' => $price,
                'field' => '元',
                'name' => '商户充值总金额'
            ],
            [
                'className' => 'el-icon-s-order',
                'count' => $balance,
                'field' => '元',
                'name' => '商户当前余额'
            ],
        ];
    }

    public function destory($id, $merId)
    {
        $where = [
            $this->dao->getPk() => $id,
            'mer_id' => $merId,
        ];
        $res = $this->dao->getSearch($where)->find();
        if (!$res) throw new ValidateException('订单不存在');

        return $this->dao->delete($id);
    }

    /**
     * TODO 订单SN
     * @return string
     * @author Qinii
     * @day 2/17/22
     */
    public function getOrderSn()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $orderId = 'dc' . $msectime . random_int(10000, max(intval($msec * 10000) + 10000, 98369));
        return $orderId;
    }

    public function show(int $id, int $uid)
    {
        $where['order_id'] = $id;
        $where['uid'] = $uid;
        $res = $this->dao->getSearch($where)->with(['storeOrderStatus','storeOrder'])->find();
        if (!$res) throw new ValidateException('订单不存在');
        return $res;
    }

}
