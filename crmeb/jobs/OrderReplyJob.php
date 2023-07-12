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


use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\order\StoreOrderStatusRepository;
use app\common\repositories\store\product\ProductReplyRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

class OrderReplyJob implements JobInterface
{

    public function fire($job, $orderId)
    {
        $storeOrderRepository = app()->make(StoreOrderRepository::class);
        $productReplyRepository = app()->make(ProductReplyRepository::class);
        $order = $storeOrderRepository->getWhere(['order_id' => $orderId, 'status' => 2]);
        if ($order) {
            $data = ['comment' => '系统默认好评', 'product_score' => 5, 'service_score' => 5, 'postage_score' => 5, 'rate' => 5, 'sort' =>0];
            $data['uid'] = $order->uid;
            $data['nickname'] = $order->user ? $order->user['nickname'] : '****';
            $data['avatar'] = $order->user ? $order->user['avatar'] : '';
            $data['mer_id'] = $order->mer_id;
            $ids = [];
            //订单记录
            $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
            $orderStatus = [
                'order_id' => $order->order_id,
                'order_sn' => $order->order_sn,
                'type' => $storeOrderStatusRepository::TYPE_ORDER,
                'change_message' => '交易完成',
                'change_type' => $storeOrderStatusRepository::ORDER_STATUS_AUTO_OVER,
            ];
            try {
                Db::transaction(function () use ($productReplyRepository, $order, &$ids, $data,$storeOrderStatusRepository,$orderStatus) {
                    foreach ($order->orderProduct as $orderProduct) {
                        if ($orderProduct->is_reply) continue;
                        $data['order_product_id'] = $orderProduct['order_product_id'];
                        $data['product_type'] = $orderProduct['cart_info']['product']['product_type']??0;
                        $ids[] = $data['product_id'] = $orderProduct['product_id'];
                        $data['unique'] = $orderProduct['cart_info']['productAttr']['unique'];
                        $productReplyRepository->create($data);
                        $orderProduct->is_reply = 1;
                        $orderProduct->save();
                    }
                    $order->status = 3;
                    $order->save();
                    //TODO 交易完成
                    $storeOrderStatusRepository->createSysLog($orderStatus);
                });
                foreach ($ids as $id) {
                    Queue::push(UpdateProductReplyJob::class, $id);
                }
            } catch (\Exception $e) {
                Log::error($orderId . '自动评价商品失败' . $e->getMessage());
            }
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
