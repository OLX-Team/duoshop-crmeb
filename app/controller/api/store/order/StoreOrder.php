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


namespace app\controller\api\store\order;


use app\common\repositories\delivery\DeliveryOrderRepository;
use app\common\repositories\store\order\StoreOrderCreateRepository;
use app\common\repositories\store\order\StoreOrderReceiptRepository;
use app\validate\api\UserReceiptValidate;
use crmeb\basic\BaseController;
use app\common\repositories\store\order\StoreCartRepository;
use app\common\repositories\store\order\StoreGroupOrderRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use crmeb\services\ExpressService;
use crmeb\services\LockService;
use think\App;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * Class StoreOrder
 * @package app\controller\api\store\order
 * @author xaboy
 * @day 2020/6/10
 */
class StoreOrder extends BaseController
{
    /**
     * @var StoreOrderRepository
     */
    protected $repository;

    /**
     * StoreOrder constructor.
     * @param App $app
     * @param StoreOrderRepository $repository
     */
    public function __construct(App $app, StoreOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function v2CheckOrder(StoreCartRepository $cartRepository, StoreOrderCreateRepository $orderCreateRepository)
    {
        $cartId = (array)$this->request->param('cart_id', []);
        $addressId = (int)$this->request->param('address_id');
        $couponIds = (array)$this->request->param('use_coupon', []);
        $takes = (array)$this->request->param('takes', []);
        $useIntegral = (bool)$this->request->param('use_integral', false);
        $user = $this->request->userInfo();
        $uid = $user->uid;
        if (!($count = count($cartId)) || $count != count($cartRepository->validIntersection($cartId, $uid)))
            return app('json')->fail('数据无效');
        $orderInfo = $orderCreateRepository->v2CartIdByOrderInfo($user, $cartId, $takes, $couponIds, $useIntegral, $addressId);

        return app('json')->success($orderInfo);
    }

    public function v2CreateOrder(StoreCartRepository $cartRepository, StoreOrderCreateRepository $orderCreateRepository)
    {
        $cartId = (array)$this->request->param('cart_id', []);
        $addressId = (int)$this->request->param('address_id');
        $couponIds = (array)$this->request->param('use_coupon', []);
        $takes = (array)$this->request->param('takes', []);
        $useIntegral = (bool)$this->request->param('use_integral', false);
        $receipt_data = (array)$this->request->param('receipt_data', []);
        $extend = (array)$this->request->param('extend', []);
        $mark = (array)$this->request->param('mark', []);
        $payType = $this->request->param('pay_type');
        $post = (array)$this->request->param('post');

        $isPc = $payType === 'pc';
        if ($isPc) {
            $payType = 'balance';
        }

        if (!in_array($payType, StoreOrderRepository::PAY_TYPE, true))
            return app('json')->fail('请选择正确的支付方式');

        $validate = app()->make(UserReceiptValidate::class);
        foreach ($receipt_data as $receipt) {
            if (!is_array($receipt)) throw new ValidateException('发票信息有误');
            $validate->check($receipt);
        }

        $uid = $this->request->uid();
        if (!($count = count($cartId)) || $count != count($cartRepository->validIntersection($cartId, $uid)))
            return app('json')->fail('数据无效');
//        if (!$addressId)
//            return app('json')->fail('请选择地址');

        $groupOrder = app()->make(LockService::class)->exec('order.create', function () use ($orderCreateRepository, $receipt_data, $mark, $extend, $cartId, $payType, $takes, $couponIds, $useIntegral, $addressId, $post) {
            return $orderCreateRepository->v2CreateOrder(array_search($payType, StoreOrderRepository::PAY_TYPE), $this->request->userInfo(), $cartId, $extend, $mark, $receipt_data, $takes, $couponIds, $useIntegral, $addressId, $post);
        });

        if ($groupOrder['pay_price'] == 0) {
            $this->repository->paySuccess($groupOrder);
            return app('json')->status('success', '支付成功', ['order_id' => $groupOrder['group_order_id']]);
        }
        if ($isPc) {
            return app('json')->success(['order_id' => $groupOrder->group_order_id]);
        }
        try {
            return $this->repository->pay($payType, $this->request->userInfo(), $groupOrder, $this->request->param('return_url'), $this->request->isApp());
        } catch (\Exception $e) {
            return app('json')->status('error', $e->getMessage(), ['order_id' => $groupOrder->group_order_id]);
        }
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where['status'] = $this->request->param('status');
        $where['search'] = $this->request->param('store_name');
        $where['uid'] = $this->request->uid();
        $where['paid'] = 1;
        $where['is_user'] = 1;
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020/6/10
     */
    public function detail($id)
    {
        $order = $this->repository->getDetail((int)$id, $this->request->uid());
        if (!$order)
            return app('json')->fail('订单不存在');
        if ($order->order_type == 1) {
            $order->append(['take', 'refund_status']);
        }
        return app('json')->success($order->toArray());
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020/6/10
     */
    public function number()
    {
        return app('json')->success(['orderPrice' => $this->request->userInfo()->pay_price] + $this->repository->userOrderNumber($this->request->uid()));
    }

    /**
     * @param StoreGroupOrderRepository $groupOrderRepository
     * @return mixed
     * @author xaboy
     * @day 2020/6/10
     */
    public function groupOrderList(StoreGroupOrderRepository $groupOrderRepository)
    {
        [$page, $limit] = $this->getPage();
        $list = $groupOrderRepository->getList(['uid' => $this->request->uid(), 'paid' => 0], $page, $limit);
        return app('json')->success($list);
    }

    /**
     * @param $id
     * @param StoreGroupOrderRepository $groupOrderRepository
     * @return mixed
     * @author xaboy
     * @day 2020/6/10
     */
    public function groupOrderDetail($id, StoreGroupOrderRepository $groupOrderRepository)
    {
        $groupOrder = $groupOrderRepository->detail($this->request->uid(), (int)$id);
        if (!$groupOrder)
            return app('json')->fail('订单不存在');
        else
            return app('json')->success($groupOrder->append(['cancel_time', 'cancel_unix'])->toArray());
    }

    public function groupOrderStatus($id, StoreGroupOrderRepository $groupOrderRepository)
    {
        $groupOrder = $groupOrderRepository->status($this->request->uid(), intval($id));
        if (!$groupOrder)
            return app('json')->fail('订单不存在');
        if ($groupOrder->paid) $groupOrder->append(['give_coupon']);
        $activity_type = 0;
        $activity_id = 0;
        foreach ($groupOrder->orderList as $order) {
            $activity_type = max($order->activity_type, $activity_type);
            if ($order->activity_type == 4 && $groupOrder->paid) {
                $order->append(['orderProduct']);
                $activity_id = $order->orderProduct[0]['activity_id'];
            }
        }
        $groupOrder->activity_type = $activity_type;
        $groupOrder->activity_id = $activity_id;
        return app('json')->success($groupOrder->toArray());
    }

    /**
     * @param $id
     * @param StoreGroupOrderRepository $groupOrderRepository
     * @return mixed
     * @author xaboy
     * @day 2020/6/10
     */
    public function cancelGroupOrder($id, StoreGroupOrderRepository $groupOrderRepository)
    {
        $groupOrderRepository->cancel((int)$id, $this->request->uid());
        return app('json')->success('取消成功');
    }

    public function groupOrderPay($id, StoreGroupOrderRepository $groupOrderRepository)
    {
        //TODO 佣金结算,佣金退回,物流查询
        $type = $this->request->param('type');
        $is_points = $this->request->param('is_points',0);
        if (!in_array($type, StoreOrderRepository::PAY_TYPE))
            return app('json')->fail('请选择正确的支付方式');
        $groupOrder = $groupOrderRepository->detail($this->request->uid(), (int)$id, false);
        if (!$groupOrder)
            return app('json')->fail('订单不存在或已支付');
        $this->repository->changePayType($groupOrder, array_search($type, StoreOrderRepository::PAY_TYPE));
        if ($groupOrder['pay_price'] == 0) {
            $this->repository->paySuccess($groupOrder);
            return app('json')->status('success', '支付成功', ['order_id' => $groupOrder['group_order_id']]);
        }

        try {
            return $this->repository->pay($type, $this->request->userInfo(), $groupOrder, $this->request->param('return_url'), $this->request->isApp());
        } catch (\Exception $e) {
            return app('json')->status('error', $e->getMessage(), ['order_id' => $groupOrder->group_order_id]);
        }
    }

    public function take($id)
    {
        $this->repository->takeOrder($id, $this->request->userInfo());
        return app('json')->success('确认收货成功');
    }

    public function express($id)
    {
        $order = $this->repository->getWhere(['order_id' => $id, 'is_del' => 0]);
        if (!$order)
            return app('json')->fail('订单不存在');
        if (!$order->delivery_type || !$order->delivery_id)
            return app('json')->fail('订单未发货');
        $express = $this->repository->express($id,null);
        $order->append(['orderProduct']);
        return app('json')->success(compact('express', 'order'));
    }

    public function verifyCode($id)
    {
        $order = $this->repository->getWhere(['order_id' => $id, 'uid' => $this->request->uid(), 'is_del' => 0, 'order_type' => 1]);
        if (!$order)
            return app('json')->fail('订单状态有误');
        return app('json')->success(['qrcode' => $this->repository->wxQrcode($id, $order->verify_code)]);
    }

    public function del($id)
    {
        $this->repository->userDel($id, $this->request->uid());
        return app('json')->success('删除成功');
    }

    public function createReceipt($id)
    {
        $data = $this->request->params(['receipt_type' , 'receipt_title' , 'duty_paragraph', 'receipt_title_type', 'bank_name', 'bank_code', 'address','tel', 'email']);
        $order = $this->repository->getWhere(['order_id' => $id, 'uid' => $this->request->uid(), 'is_del' => 0]);
        if (!$order) return app('json')->fail('订单不属于您或不存在');
        app()->make(StoreOrderReceiptRepository::class)->add($data, $order);
        return app('json')->success('操作成功');
    }

    public function getOrderDelivery($id, DeliveryOrderRepository $orderRepository)
    {
        $res = $orderRepository->show($id, $this->request->uid());
        return app('json')->success($res);
    }
}
