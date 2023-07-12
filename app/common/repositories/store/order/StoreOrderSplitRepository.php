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

use app\common\dao\store\order\StoreOrderDao;
use app\common\model\store\order\StoreOrder;
use crmeb\services\LockService;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class StoreOrderSplitRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2022/3/10
 * @mixin StoreOrderDao
 */
class StoreOrderSplitRepository extends StoreOrderRepository
{

    public function splitOrder(StoreOrder $order, array $rule, $service_id = 0,$type = null)
    {
        return app()->make(LockService::class)->exec('order.split.' . $order->order_id, function () use ($rule, $order,$service_id,$type) {
            return $this->execSplitOrder($order, $rule, $service_id,$type);
        });
    }

    /**
     * TODO
     * @param StoreOrder $order
     * @param array $rule
     * @param $service_id
     * @param $type 是否未核销
     * @day 2023/4/3
     */
    public function execSplitOrder(StoreOrder $order, array $rule, $service_id = 0, $type = null)
    {
        if ($order['status'] != 0) {
            throw new ValidateException('订单已发货');
        }
        if ($order['activity_type'] == 2 && !$type) {
            throw new ValidateException('预售订单不能拆单');
        }
        return Db::transaction(function () use ($order, $rule,$service_id) {
            $newOrderId = 0;
            $newOrder = $order->getOrigin();
            $newOrder['total_num'] = 0;
            $newOrder['total_price'] = 0;
            $newOrder['total_postage'] = 0;
            $newOrder['pay_price'] = 0;
            $newOrder['pay_postage'] = 0;
            $newOrder['extension_one'] = 0;
            $newOrder['extension_two'] = 0;
            $newOrder['coupon_price'] = 0;
            $newOrder['platform_coupon_price'] = 0;
            $newOrder['svip_discount'] = 0;
            $newOrder['cost'] = 0;
            $newOrder['integral_price'] = 0;
            $newOrder['integral'] = 0;
            $newOrder['give_integral'] = 0;
            $newOrder['coupon_id'] = '';

            $inserts = [];

            $flag = false;

            foreach ($order['orderProduct'] as $product) {
                if (!isset($rule[$product['order_product_id']])) {
                    if ($product['refund_num'] > 0) {
                        $flag = true;
                    }
                    continue;
                }
                $num = (int)$rule[$product['order_product_id']];
                if ($num <= 0) {
                    throw new ValidateException('拆单数必须大于0');
                }
                if ($num > $product['refund_num']) {
                    throw new ValidateException('商品超出最大拆单数');
                }
                if ($num != $product['refund_num']) {
                    $flag = true;
                }
            }

            if (!$flag) {
                return $flag;
                //throw new ValidateException('商品不能全部拆单');
            }

            foreach ($order['orderProduct'] as $product) {
                if (!isset($rule[$product['order_product_id']])) continue;
                $num = (int)$rule[$product['order_product_id']];

                $newProduct = $product->getOrigin();
                unset($newProduct['order_product_id'], $newProduct['create_time']);
                $newProduct['order_id'] = &$newOrderId;
                $newProduct['is_refund'] = 0;
                $newProduct['is_reply'] = 0;

                if ($product['product_num'] == $num && !$product['is_refund']) {
                    $product->delete();
                } else {
                    $newProduct['product_num'] = $num;
                    $newProduct['refund_num'] = $num;

                    if (!$product['refund_num'] && $product['is_refund'] == 2) {
                        $product['is_refund'] = 3;
                    }

                    $newProduct['product_price'] = $product['product_price'] > 0 ? round(bcmul(bcdiv($product['product_price'], $product['product_num'], 3), $num, 3), 2) : 0;
                    $newProduct['total_price'] = $product['total_price'] > 0 ? round(bcmul(bcdiv($product['total_price'], $product['product_num'], 2), $num, 2), 2) : 0;
                    $newProduct['extension_one'] = $product['extension_one'];
                    $newProduct['extension_two'] = $product['extension_two'];
                    $newProduct['coupon_price'] = $product['coupon_price'] > 0 ? bcmul(bcdiv($product['coupon_price'], $product['product_num'], 2), $num, 2) : 0;
                    $newProduct['svip_discount'] = $product['svip_discount'] > 0 ? bcmul(bcdiv($product['svip_discount'], $product['product_num'], 2), $num, 2) : 0;
                    $newProduct['integral_price'] = $product['integral_price'] > 0 ? bcmul(bcdiv($product['integral_price'], $product['product_num'], 2), $num, 2) : 0;
                    $newProduct['platform_coupon_price'] = $product['platform_coupon_price'] > 0 ? bcmul(bcdiv($product['platform_coupon_price'], $product['product_num'], 2), $num, 2) : 0;
                    $newProduct['postage_price'] = $product['postage_price'] > 0 ? bcmul(bcdiv($product['postage_price'], $product['product_num'], 2), $num, 2) : 0;
                    $newProduct['integral_total'] = $product['integral_total'] > 0 ? floor(bcmul(bcdiv($product['integral_total'], $product['integral_total'], 2), $num, 0)) : 0;

                    $product['product_price'] = $product['product_price'] > 0 ? bcsub($product['product_price'], $newProduct['product_price'], 2) : 0;
                    $product['total_price'] = $product['total_price'] > 0 ? bcsub($product['total_price'], $newProduct['total_price'], 2) : 0;
                    $product['coupon_price'] = $product['coupon_price'] > 0 ? bcsub($product['coupon_price'], $newProduct['coupon_price'], 2) : 0;
                    $product['svip_discount'] = $product['svip_discount'] > 0 ? bcsub($product['svip_discount'], $newProduct['svip_discount'], 2) : 0;
                    $product['integral_price'] = $product['integral_price'] > 0 ? bcsub($product['integral_price'], $newProduct['integral_price'], 2) : 0;
                    $product['platform_coupon_price'] = $product['platform_coupon_price'] > 0 ? bcsub($product['platform_coupon_price'], $newProduct['platform_coupon_price'], 2) : 0;
                    $product['postage_price'] = $product['postage_price'] > 0 ? bcsub($product['postage_price'], $newProduct['postage_price'], 2) : 0;
                    $product['integral_total'] = $product['integral_total'] > 0 ? bcsub($product['integral_total'], $newProduct['integral_total'], 0) : 0;

                    $product['product_num'] -= $num;
                    $product['refund_num'] -= $num;

                    $product->save();
                }

                $give_integral = $order['give_integral'] > 0 ? floor(bcmul(bcdiv($newProduct['product_price'], $order['pay_price'], 2), $order['give_integral'], 0)) : 0;
                $extension_one = $newProduct['extension_one'] > 0 ? bcmul($newProduct['extension_one'], $num, 2) : 0;
                $extension_two = $newProduct['extension_two'] > 0 ? bcmul($newProduct['extension_two'], $num, 2) : 0;
                $order['total_num'] -= $newProduct['product_num'];
                $order['total_price'] = $order['total_price'] > 0 ? bcsub($order['total_price'], $newProduct['total_price'], 2) : 0;
                $order['total_postage'] = $order['total_postage'] > 0 ? bcsub($order['total_postage'], $newProduct['postage_price'], 2) : 0;
                $order['pay_postage'] = $order['total_postage'];
                $order['extension_one'] = $order['extension_one'] > 0 ? bcsub($order['extension_one'], $extension_one, 2) : 0;
                $order['extension_two'] = $order['extension_two'] > 0 ? bcsub($order['extension_two'], $extension_two, 2) : 0;
                $order['svip_discount'] = $order['svip_discount'] > 0 ? bcsub($order['svip_discount'], $newProduct['svip_discount'], 2) : 0;
                $order['coupon_price'] = $order['coupon_price'] > 0 ? bcsub($order['coupon_price'], $newProduct['coupon_price'], 2) : 0;
                $order['coupon_price'] = $order['platform_coupon_price'] > 0 ? bcsub($order['coupon_price'], $newProduct['platform_coupon_price'], 2) : $order['coupon_price'];
                $order['platform_coupon_price'] = $order['platform_coupon_price'] > 0 ? bcsub($order['platform_coupon_price'], $newProduct['platform_coupon_price'], 2) : 0;
                $order['cost'] = $order['cost'] > 0 ? bcsub($order['cost'], $newProduct['cost'], 2) : 0;
                $order['integral_price'] = $order['integral_price'] > 0 ? bcsub($order['integral_price'], $newProduct['integral_price'], 2) : 0;
                $order['integral'] = $order['integral'] > 0 ? bcsub($order['integral'], $newProduct['integral_total'], 2) : 0;
                $order['give_integral'] = ($order['give_integral'] > 0 && $newProduct['total_price'] > 0) ? bcsub($order['give_integral'], $give_integral, 0) : 0;
                $order['pay_price'] = $order['pay_price'] > 0 ? bcsub($order['pay_price'], $newProduct['product_price'], 2) : 0;

                $newOrder['total_num'] += $newProduct['product_num'];
                $newOrder['total_price'] = bcadd($newOrder['total_price'], $newProduct['total_price'], 2);
                $newOrder['total_postage'] = bcadd($newOrder['total_postage'], $newProduct['postage_price'], 2);
                $newOrder['pay_postage'] = $newOrder['total_postage'];
                $newOrder['extension_one'] = bcadd($newOrder['extension_one'], $extension_one, 2);
                $newOrder['extension_two'] = bcadd($newOrder['extension_two'], $extension_two, 2);
                $newOrder['svip_discount'] = bcadd($newOrder['svip_discount'], $newProduct['svip_discount'], 2);
                $newOrder['coupon_price'] = bcadd($newOrder['coupon_price'], $newProduct['coupon_price'], 2);
                $newOrder['coupon_price'] = bcadd($newOrder['coupon_price'], $newProduct['platform_coupon_price'], 2);
                $newOrder['platform_coupon_price'] = bcadd($newOrder['platform_coupon_price'], $newProduct['platform_coupon_price'], 2);
                $newOrder['cost'] = bcadd($newOrder['cost'], $newProduct['cost'], 2);
                $newOrder['integral_price'] = bcadd($newOrder['integral_price'], $newProduct['integral_price'], 2);
                $newOrder['integral'] = bcadd($newOrder['integral'], $newProduct['integral_total'], 2);
                $newOrder['give_integral'] = bcadd($newOrder['give_integral'], $give_integral, 2);
                $newOrder['pay_price'] = bcadd($newOrder['pay_price'], $newProduct['product_price'], 2);

                $inserts[] = $newProduct;
            }

            if (!count($inserts)) {
                throw new ValidateException('请选择需拆出的商品');
            }

            $newOrder['pay_price'] = bcadd($newOrder['pay_price'], $newOrder['pay_postage'], 2);
            $order['pay_price'] = bcsub($order['pay_price'], $newOrder['pay_postage'], 2);
            $newOrder['order_sn'] = explode('-', $newOrder['order_sn'])[0] . ('-' . ($this->getSearch([])->where('main_id', $order['order_id'])->count() + 1));
            $newOrder['main_id'] = $order['main_id'] ?: $order['order_id'];
            if ($newOrder['verify_code']) {
                $newOrder['verify_code'] = $this->verifyCode();
            }
            unset($newOrder['order_id']);
            $newOrder = $this->create($newOrder);
            $newOrderId = $newOrder['order_id'];
            app()->make(StoreOrderProductRepository::class)->insertAll($inserts);

            $flag = true;
            foreach ($order['orderProduct'] as $product) {
                $flag = $flag && $product['is_refund'] == 3;
            }
            if ($flag) {
                $order['status'] = -1;
            }
            $order->save();
            if ($flag) {
                $this->orderRefundAllAfter($order);
            }
            $statusRepository = app()->make(StoreOrderStatusRepository::class);
            $orderStatus = [
                'order_id' => $order->order_id,
                'order_sn' => $order->order_sn,
                'type' => $statusRepository::TYPE_ORDER,
                'change_message' => '生成子订单：'.$newOrder->order_sn,
                'change_type' => $statusRepository::ORDER_STATUS_SPLIT,
            ];
            $newOrderStatus = [
                'order_id' => $newOrder->order_id,
                'order_sn' => $newOrder->order_sn,
                'type' => $statusRepository::TYPE_ORDER,
                'change_message' => '生成子订单',
                'change_type' => $statusRepository::ORDER_STATUS_SPLIT,
            ];

            if ($service_id) {
                $statusRepository->createServiceLog($service_id,$orderStatus);
                $statusRepository->createServiceLog($service_id,$newOrderStatus);
            } else {
                $statusRepository->createAdminLog($orderStatus);
                $statusRepository->createAdminLog($newOrderStatus);
            }
            return $newOrder;
        });
    }
}
