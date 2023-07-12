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


use app\common\dao\store\order\PresellOrderDao;
use app\common\model\store\order\PresellOrder;
use app\common\model\user\User;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductPresellSkuRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\system\merchant\FinancialRecordRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserMerchantRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\jobs\UserBrokerageLevelJob;
use crmeb\services\AlipayService;
use crmeb\services\CombinePayService;
use crmeb\services\MiniProgramService;
use crmeb\services\PayService;
use crmeb\services\WechatService;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

/**
 * Class PresellOrderRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/10/27
 * @mixin PresellOrderDao
 */
class PresellOrderRepository extends BaseRepository
{
    public function __construct(PresellOrderDao $dao)
    {
        $this->dao = $dao;
    }

    public function createOrder($uid, $orderId, $price, $final_start_time, $final_end_time)
    {
        return $this->dao->create([
            'uid' => $uid,
            'order_id' => $orderId,
            'final_start_time' => $final_start_time,
            'final_end_time' => $final_end_time,
            'pay_price' => $price,
            'presell_order_sn' => app()->make(StoreOrderRepository::class)->getNewOrderId(StoreOrderRepository::TYPE_SN_PRESELL)
        ]);
    }

    public function pay($type, User $user, PresellOrder $order, $return_url = '', $isApp = false)
    {
        if ($type === 'balance') {
            return $this->payBalance($user, $order);
        }

        if (in_array($type, ['weixin', 'alipay'], true) && $isApp) {
            $type .= 'App';
        }
        event('order.presell.pay.before', compact('order', 'type', 'isApp'));
        if (in_array($type, ['weixin', 'weixinApp', 'routine', 'h5', 'weixinQr'], true) && systemConfig('open_wx_combine')) {
            $service = new CombinePayService($type, $order->getCombinePayParams());
        } else {
            $service = new PayService($type, $order->getPayParams($type === 'alipay' ? $return_url : ''), 'presell');
        }
        $config = $service->pay($user);
        return app('json')->status($type, $config + ['order_id' => $order['presell_order_id']]);
    }

    /**
     * @param User $user
     * @param PresellOrder $order
     * @return mixed
     * @author xaboy
     * @day 2020/6/9
     */
    public function payBalance(User $user, PresellOrder $order)
    {
        if (!systemConfig('yue_pay_status'))
            throw new ValidateException('未开启余额支付');
        if ($user['now_money'] < $order['pay_price'])
            throw new ValidateException('余额不足，请更换支付方式');
        Db::transaction(function () use ($user, $order) {
            $user->now_money = bcsub($user->now_money, $order['pay_price'], 2);
            $user->save();
            $userBillRepository = app()->make(UserBillRepository::class);
            $userBillRepository->decBill($user['uid'], 'now_money', 'presell', [
                'link_id' => $order['presell_order_id'],
                'status' => 1,
                'title' => '支付预售尾款',
                'number' => $order['pay_price'],
                'mark' => '余额支付支付' . floatval($order['pay_price']) . '元购买商品',
                'balance' => $user->now_money
            ]);
            $this->paySuccess($order);
        });
        return app('json')->status('success', '余额支付成功', ['order_id' => $order['presell_order_id']]);
    }

    public function paySuccess(PresellOrder $order, $is_combine = 0, array $subOrders = [])
    {
        Db::transaction(function () use ($is_combine, $order, $subOrders) {
            $time = date('Y-m-d H:i:s');
            $order->paid = 1;
            $order->pay_time = $time;
            if (isset($subOrders[$order->presell_order_sn])) {
                $order->transaction_id = $subOrders[$order->presell_order_sn]['transaction_id'];
            }
            $order->is_combine = $is_combine;
            $order->order->status = 0;
            if ($order->order->order_type == 1) {
                $order->order->verify_code = app()->make(StoreOrderRepository::class)->verifyCode();
            }
            $order->order->save();
            $order->save();
            //订单记录
            $statusRepository = app()->make(StoreOrderStatusRepository::class);
            $orderStatus = [
                'order_id' => $order->order_id,
                'order_sn' => $order->order_sn,
                'type' => $statusRepository::TYPE_ORDER,
                'change_message' => '订单尾款支付成功',
                'change_type' => $statusRepository::ORDER_STATUS_PRESELL,
            ];
            $i = 1;
            $finance = [];

            $final_price = $order->order->pay_price;
            $order_price = $order->pay_price;
            $pay_price = bcadd($order_price, $final_price, 2);
            $sn = app()->make(FinancialRecordRepository::class)->getSn();

            $finance[] = [
                'order_id' => $order->order_id,
                'order_sn' => $order->presell_order_sn,
                'user_info' => $order->user->nickname,
                'user_id' => $order->uid,
                'financial_type' => 'presell',
                'financial_pm' => 1,
                'type' => 2,
                'number' => $order->pay_price,
                'mer_id' => $order->mer_id,
                'financial_record_sn' => $sn . ($i++)
            ];

            $finance[] = [
                'order_id' => $order->order->order_id,
                'order_sn' => $order->order->order_sn,
                'user_info' => $order->user->nickname,
                'user_id' => $order->uid,
                'financial_type' => 'mer_presell',
                'financial_pm' => 1,
                'type' => 0,
                'number' => $pay_price,
                'mer_id' => $order->mer_id,
                'financial_record_sn' => $sn . ($i++)
            ];

//            $pay_price = bcsub($pay_price, bcadd($order->order['extension_one'], $order->order['extension_two'], 3), 2);
            if (isset($order->order->orderProduct[0]['cart_info']['presell_extension_one']) && $order->order->orderProduct[0]['cart_info']['presell_extension_one'] > 0) {
                $order_price = bcsub($order_price, $order->order->orderProduct[0]['cart_info']['presell_extension_one'], 2);
            }
            if (isset($order->order->orderProduct[0]['cart_info']['presell_extension_two']) && $order->order->orderProduct[0]['cart_info']['presell_extension_two'] > 0) {
                $order_price = bcsub($order_price, $order->order->orderProduct[0]['cart_info']['presell_extension_two'], 2);
            }
            if (isset($order->order->orderProduct[0]['cart_info']['final_extension_one']) && $order->order->orderProduct[0]['cart_info']['final_extension_one'] > 0) {
                $final_price = bcsub($final_price, $order->order->orderProduct[0]['cart_info']['final_extension_one'], 2);
            }
            if (isset($order->order->orderProduct[0]['cart_info']['final_extension_two']) && $order->order->orderProduct[0]['cart_info']['final_extension_two'] > 0) {
                $final_price = bcsub($final_price, $order->order->orderProduct[0]['cart_info']['final_extension_two'], 2);
            }
            if ($order->order['extension_one'] > 0) {
                $finance[] = [
                    'order_id' => $order->order->order_id,
                    'order_sn' => $order->order->order_sn,
                    'user_info' => $order->user->nickname,
                    'user_id' => $order->uid,
                    'financial_type' => 'brokerage_one',
                    'financial_pm' => 0,
                    'type' => 1,
                    'number' => $order->order['extension_one'],
                    'mer_id' => $order->mer_id,
                    'financial_record_sn' => $sn . ($i++)
                ];
            }

            if ($order->order['extension_two'] > 0) {
                $finance[] = [
                    'order_id' => $order->order->order_id,
                    'order_sn' => $order->order->order_sn,
                    'user_info' => $order->user->nickname,
                    'user_id' => $order->uid,
                    'financial_type' => 'brokerage_two',
                    'financial_pm' => 0,
                    'type' => 1,
                    'number' => $order->order['extension_two'],
                    'mer_id' => $order->mer_id,
                    'financial_record_sn' => $sn . ($i++)
                ];
            }

            if ($order->order->commission_rate > 0) {
                $commission_rate = ($order->order->commission_rate / 100);
                $finalRatePrice = bcmul($final_price, $commission_rate, 2);
                $orderRatePrice = bcmul($order_price, $commission_rate, 2);
                $ratePrice = bcadd($finalRatePrice, $orderRatePrice, 2);
                $finance[] = [
                    'order_id' => $order->order->order_id,
                    'order_sn' => $order->order->order_sn,
                    'user_info' => $order->user->nickname,
                    'user_id' => $order->uid,
                    'financial_type' => 'presell_charge',
                    'financial_pm' => 1,
                    'type' => 1,
                    'number' => $ratePrice,
                    'mer_id' => $order->mer_id,
                    'financial_record_sn' => $sn . ($i++)
                ];
//                $pay_price = bcsub($pay_price, $ratePrice, 2);
                $order_price = bcsub($order_price, $orderRatePrice, 2);
                $final_price = bcsub($final_price, $finalRatePrice, 2);
            }
            $finance[] = [
                'order_id' => $order->order->order_id,
                'order_sn' => $order->order->order_sn,
                'user_info' => $order->user->nickname,
                'user_id' => $order->uid,
                'financial_type' => 'presell_true',
                'financial_pm' => 1,
                'type' => 2,
                'number' => bcadd($order_price, $final_price, 2),
                'mer_id' => $order->mer_id,
                'financial_record_sn' => $sn . ($i++)
            ];
            if (!$is_combine) {
                app()->make(MerchantRepository::class)->addLockMoney($order->mer_id, 'presell', $order->presell_order_id, !$order->order->groupOrder->is_combine ? bcadd($order_price, $final_price, 2) : $order_price);
//                app()->make(MerchantRepository::class)->addMoney($order->mer_id, !$order->order->groupOrder->is_combine ? bcadd($order_price, $final_price, 2) : $order_price);
            } else if (!$order->order->groupOrder->is_combine) {
                app()->make(MerchantRepository::class)->addLockMoney($order->mer_id, 'presell', $order->presell_order_id, $final_price);
//                app()->make(MerchantRepository::class)->addMoney($order->mer_id, $final_price);
            }

            if ($is_combine) {
                $storeOrderProfitsharingRepository = app()->make(StoreOrderProfitsharingRepository::class);
                $storeOrderProfitsharingRepository->create([
                    'profitsharing_sn' => $storeOrderProfitsharingRepository->getOrderSn(),
                    'order_id' => $order->order->order_id,
                    'mer_id' => $order->mer_id,
                    'transaction_id' => $order->transaction_id ?? '',
                    'profitsharing_price' => $order->pay_price,
                    'profitsharing_mer_price' => $order_price,
                    'type' => $storeOrderProfitsharingRepository::PROFITSHARING_TYPE_PRESELL,
                ]);
            }
            app()->make(UserRepository::class)->update($order->uid, [
                'pay_price' => Db::raw('pay_price+' . $order->pay_price),
            ]);
            app()->make(ProductPresellSkuRepository::class)->incCount($order->order->orderProduct[0]['activity_id'], $order->order->orderProduct[0]['product_sku'], 'two_pay');
            app()->make(UserMerchantRepository::class)->updatePayTime($order->uid, $order->mer_id, $order->pay_price, false);
            app()->make(FinancialRecordRepository::class)->insertAll($finance);
            $statusRepository->createUserLog($orderStatus);
        });
        if ($order->user->spread_uid) {
            Queue::push(UserBrokerageLevelJob::class, ['uid' => $order->user->spread_uid, 'type' => 'spread_money', 'inc' => $order->pay_price]);
        }
        Queue::push(UserBrokerageLevelJob::class, ['uid' => $order->uid, 'type' => 'pay_money', 'inc' => $order->pay_price]);
        event('order.presll.paySuccess', compact('order'));
    }

    public function cancel($id)
    {
        $order = $this->dao->getWhere(['presell_order_id' => $id, 'paid' => 0]);
        if (!$order) return;
        //订单记录
        $statusRepository = app()->make(StoreOrderStatusRepository::class);

        $orderStatus = [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'type' => $statusRepository::TYPE_ORDER,
            'change_message' => '预售订单超时支付自动关闭',
            'change_type' => $statusRepository::ORDER_STATUS_PRESELL_CLOSE,
        ];
        event('order.presll.fail.before', compact('order'));
        $productRepository = app()->make(ProductRepository::class);
        Db::transaction(function () use ($productRepository, $order, $orderStatus,$statusRepository) {
            $statusRepository->createSysLog($orderStatus);
            $order->order->status = 11;
            $order->status = 0;
            $order->save();+
            $order->order->save();
            foreach ($order->order->orderProduct as $cart) {
                $productRepository->orderProductIncStock($order->order, $cart);
            }
            if ($order->order->firstProfitsharing && $order->order->firstProfitsharing->profitsharing_price > 0) {
                $make = app()->make(FinancialRecordRepository::class);
                $sn = $make->getSn();
                $financial = [[
                    'order_id' => $order->order->order_id,
                    'order_sn' => $order->order->order_sn,
                    'user_info' => $order->user->nickname,
                    'user_id' => $order->uid,
                    'financial_type' => 'presell_charge',
                    'financial_pm' => 1,
                    'type' => 1,
                    'number' => bcsub($order->order->firstProfitsharing->profitsharing_price, $order->order->firstProfitsharing->profitsharing_mer_price, 2),
                    'mer_id' => $order->mer_id,
                    'financial_record_sn' => $sn . '0'
                ], [
                    'order_id' => $order->order->order_id,
                    'order_sn' => $order->order->order_sn,
                    'user_info' => $order->user->nickname,
                    'user_id' => $order->uid,
                    'financial_type' => 'mer_presell',
                    'financial_pm' => 1,
                    'type' => 0,
                    'number' => $order->order->firstProfitsharing->profitsharing_mer_pric,
                    'mer_id' => $order->mer_id,
                    'financial_record_sn' => $sn . '1'
                ], [
                    'order_id' => $order->order->order_id,
                    'order_sn' => $order->order->order_sn,
                    'user_info' => $order->user->nickname,
                    'user_id' => $order->uid,
                    'financial_type' => 'presell_true',
                    'financial_pm' => 1,
                    'type' => 2,
                    'number' => $order->order->firstProfitsharing->profitsharing_mer_price,
                    'mer_id' => $order->mer_id,
                    'financial_record_sn' => $sn . '2'
                ]];
                $make->insertAll($financial);
                try {
                    app()->make(StoreOrderProfitsharingRepository::class)->profitsharing($order->order->firstProfitsharing);
                } catch (\Exception $e) {
                    Log::info('预售定金分账失败' . $order->order_id . $e->getMessage());
                }
            }
        });
        event('order.presll.fail', compact('order'));
    }
}
