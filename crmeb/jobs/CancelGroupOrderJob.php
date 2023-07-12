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


namespace crmeb\jobs;


use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\order\StoreGroupOrderRepository;
use app\common\repositories\store\product\ProductAttrValueRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Db;
use think\facade\Log;

class CancelGroupOrderJob implements JobInterface
{

    public function fire($job, $groupOrderId)
    {
        $groupOrderRepository = app()->make(StoreGroupOrderRepository::class);
        $groupOrder = $groupOrderRepository->getCancelDetail($groupOrderId);
        if (!$groupOrder) return $job->delete();
        Db::transaction(function () use ($groupOrder) {
            $couponId = $groupOrder->coupon_id ? [$groupOrder->coupon_id] : [];
            $productRepository = app()->make(ProductRepository::class);
            foreach ($groupOrder->orderList as $order) {
                if ($order->coupon_id)
                    $couponId = array_merge($couponId, explode(',', $order->coupon_id));
                foreach ($order->orderProduct as $cart) {
                    $productRepository->orderProductIncStock($order, $cart);
                }
                if ($order->activity_type == 10) {
                    app()->make(StoreDiscountRepository::class)->incStock($order->orderProduct[0]['activity_id']);
                }
            }
            if (count($couponId)) {
                app()->make(StoreCouponUserRepository::class)->updates($couponId, ['status' => 0]);
            }
        });
        return $job->delete();
    }

    public function failed($data)
    {
        Log::info('取消订单执行失败:' . var_export($data, true));
    }
}
