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
use app\common\repositories\store\order\PointsOrderCreateRepository;
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
class PointsOrder extends BaseController
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

    public function beforCheck(StoreCartRepository $cartRepository)
    {
        $cartId = (array)$this->request->param('cart_id', []);
        $addressId = (int)$this->request->param('address_id');
        $useIntegral = (bool)$this->request->param('use_integral', true);
        $params['couponIds'] = (array)$this->request->param('use_coupon', []);
        $params['takes'] = (array)$this->request->param('takes', []);
        $user = $this->request->userInfo();
        if (!($count = count($cartId)) || $count != count($cartRepository->validIntersection($cartId, $user->uid)))
            return app('json')->fail('数据无效');
        $orderInfo = app()->make(PointsOrderCreateRepository::class)->check($user, $cartId, $addressId,$useIntegral,$params);

        return app('json')->success($orderInfo);
    }

    public function createOrder(StoreCartRepository $cartRepository)
    {
        $cartId = (array)$this->request->param('cart_id', []);
        $addressId = (int)$this->request->param('address_id');
        $useIntegral = (bool)$this->request->param('use_integral', true);
        $mark = $this->request->param('mark', '');
        $payType = $this->request->param('pay_type');

        $isPc = $payType === 'pc';
        if ($isPc) $payType = 'balance';
        if (!in_array($payType, StoreOrderRepository::PAY_TYPE, true))
            return app('json')->fail('请选择正确的支付方式');

        $uid = $this->request->uid();
        if (!($count = count($cartId)) || $count != count($cartRepository->validIntersection($cartId, $uid)))
            return app('json')->fail('数据无效');
        $make = app()->make(LockService::class);

        $groupOrder = $make->exec('points.order.create', function () use ($mark, $cartId, $payType, $useIntegral, $addressId) {
            return app()->make(PointsOrderCreateRepository::class)->createOrder($this->request->userInfo(),$cartId,$addressId,$useIntegral,$mark,array_search($payType, StoreOrderRepository::PAY_TYPE));
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
     * TODO 积分商品订单
     * @param StoreOrderRepository $storeOrderRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2023/4/23
     */
    public function lst(StoreOrderRepository $storeOrderRepository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['pay_type','paid','status']);
        $where['activity_type'] = 20;
        $where['uid'] = $this->request->uid();
        return app('json')->success($storeOrderRepository->pointsOrderList($where,$page, $limit));
    }

    public function detail($id,StoreOrderRepository $storeOrderRepository)
    {
        $order = $storeOrderRepository->pointsDetail((int)$id, $this->request->uid());
        if (!$order)
            return app('json')->fail('订单不存在');
        return app('json')->success($order->toArray());
    }

    public function take($id)
    {
        $this->repository->takeOrder($id, $this->request->userInfo());
        return app('json')->success('确认收货成功');
    }

    public function del($id)
    {
        $this->repository->userDel($id, $this->request->uid());
        return app('json')->success('删除成功');
    }


}
