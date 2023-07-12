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


use app\common\repositories\store\order\PresellOrderRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use crmeb\basic\BaseController;
use think\App;
use think\exception\ValidateException;

class PresellOrder extends BaseController
{
    protected $repository;

    public function __construct(App $app, PresellOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function pay($id)
    {
        $type = $this->request->param('type');
        if (!in_array($type, StoreOrderRepository::PAY_TYPE))
            return app('json')->fail('请选择正确的支付方式');

        $order = $this->repository->userOrder($this->request->uid(), intval($id));
        if (!$order)
            throw new ValidateException('尾款订单不存在');
        if ($order->paid)
            throw new ValidateException('已支付');
        if (!$order->status)
            throw new ValidateException('尾款订单以失效');
        if (strtotime($order->final_start_time) > time())
            throw new ValidateException('未到尾款支付时间');
        if (strtotime($order->final_end_time) < time())
            throw new ValidateException('已过尾款支付时间');

        $order->pay_type = array_search($type, StoreOrderRepository::PAY_TYPE);
        $order->save();

        if ($order['pay_price'] == 0) {
            $this->repository->paySuccess($order);
            return app('json')->status('success', '支付成功', ['order_id' => $order['presell_order_id']]);
        }

        try {
            return $this->repository->pay($type, $this->request->userInfo(), $order, $this->request->param('return_url'), $this->request->isApp());
        } catch (\Exception $e) {
            return app('json')->status('error', $e->getMessage(), ['order_id' => $order->presell_order_id]);
        }
    }
}
