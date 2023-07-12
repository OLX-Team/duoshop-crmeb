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
use app\common\model\store\order\StoreGroupOrder;
use app\common\model\store\order\StoreOrder;
use app\common\model\user\User;
use app\common\repositories\BaseRepository;
use app\common\repositories\delivery\DeliveryOrderRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductAttrValueRepository;
use app\common\repositories\store\product\ProductCopyRepository;
use app\common\repositories\store\product\ProductGroupBuyingRepository;
use app\common\repositories\store\product\ProductPresellSkuRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use app\common\repositories\store\shipping\ExpressRepository;
use app\common\repositories\store\StorePrinterRepository;
use app\common\repositories\store\StoreSeckillActiveRepository;
use app\common\repositories\system\attachment\AttachmentRepository;
use app\common\repositories\system\merchant\FinancialRecordRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\serve\ServeDumpRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserBrokerageRepository;
use app\common\repositories\user\UserMerchantRepository;
use app\common\repositories\user\UserRepository;
use crmeb\jobs\PayGiveCouponJob;
use crmeb\jobs\SendSmsJob;
use crmeb\jobs\UserBrokerageLevelJob;
use crmeb\services\CombinePayService;
use crmeb\services\CrmebServeServices;
use crmeb\services\ExpressService;
use crmeb\services\PayService;
use crmeb\services\printer\Printer;
use crmeb\services\QrcodeService;
use crmeb\services\SpreadsheetExcelService;
use crmeb\services\SwooleTaskService;
use Exception;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use http\Exception\InvalidArgumentException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;
use think\facade\Route;
use think\Model;

/**
 * Class StoreOrderRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/6/9
 * @mixin StoreOrderDao
 */
class StoreOrderRepository extends BaseRepository
{
    /**
     * 支付类型
     */
    const PAY_TYPE = ['balance', 'weixin', 'routine', 'h5', 'alipay', 'alipayQr', 'weixinQr'];

    const TYPE_SN_ORDER = 'wxo';
    const TYPE_SN_PRESELL = 'wxp';
    const TYPE_SN_USER_ORDER = 'wxs';
    const TYPE_SN_USER_RECHARGE = 'wxu';
    const TYPE_SN_SERVER_ORDER = 'cs';
    const TYPE_SN_REFUND = 'rwx';
    /**
     * StoreOrderRepository constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param string $type
     * @param User $user
     * @param StoreGroupOrder $groupOrder
     * @param string $return_url
     * @return mixed
     * @author xaboy
     * @day 2020/10/22
     */
    public function pay(string $type, User $user, StoreGroupOrder $groupOrder, $return_url = '', $isApp = false)
    {

        if ($type === 'balance') {
            return $this->payBalance($user, $groupOrder);
        }

        if (in_array($type, ['weixin', 'alipay'], true) && $isApp) {
            $type .= 'App';
        }
        event('order.pay.before', compact('groupOrder', 'type', 'isApp'));
        if (in_array($type, ['weixin', 'weixinApp', 'routine', 'h5', 'weixinQr'], true) && systemConfig('open_wx_combine')) {
            $service = new CombinePayService($type, $groupOrder->getCombinePayParams());
        } else {
            $service = new PayService($type, $groupOrder->getPayParams($type === 'alipay' ? $return_url : ''));
        }
        $config = $service->pay($user);
        return app('json')->status($type, $config + ['order_id' => $groupOrder['group_order_id']]);
    }

    /**
     * @param User $user
     * @param StoreGroupOrder $groupOrder
     * @return mixed
     * @author xaboy
     * @day 2020/6/9
     */
    public function payBalance(User $user, StoreGroupOrder $groupOrder)
    {
        if (!systemConfig('yue_pay_status'))
            throw new ValidateException('未开启余额支付');
        if ($user['now_money'] < $groupOrder['pay_price'])
            throw new ValidateException('余额不足，请更换支付方式');
        Db::transaction(function () use ($user, $groupOrder) {
            $user->now_money = bcsub($user->now_money, $groupOrder['pay_price'], 2);
            $user->save();
            $userBillRepository = app()->make(UserBillRepository::class);
            $userBillRepository->decBill($user['uid'], 'now_money', 'pay_product', [
                'link_id' => $groupOrder['group_order_id'],
                'status' => 1,
                'title' => '购买商品',
                'number' => $groupOrder['pay_price'],
                'mark' => '余额支付支付' . floatval($groupOrder['pay_price']) . '元购买商品',
                'balance' => $user->now_money
            ]);
            $this->paySuccess($groupOrder);
        });
        return app('json')->status('success', '余额支付成功', ['order_id' => $groupOrder['group_order_id']]);
    }

    public function changePayType(StoreGroupOrder $groupOrder, int $pay_type)
    {
        Db::transaction(function () use ($groupOrder, $pay_type) {
            $groupOrder->pay_type = $pay_type;
            foreach ($groupOrder->orderList as $order) {
                $order->pay_type = $pay_type;
                $order->save();
            }
            $groupOrder->save();
        });
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/8/3
     */
    public function verifyCode()
    {
        $code = substr(uniqid('', true), 15) . substr(microtime(), 2, 8);
        if ($this->dao->existsWhere(['verify_code' => $code]))
            return $this->verifyCode();
        else
            return $code;
    }

    /**
     * //TODO 支付成功后
     *
     * @param StoreGroupOrder $groupOrder
     * @author xaboy
     * @day 2020/6/9
     */
    public function paySuccess(StoreGroupOrder $groupOrder, $is_combine = 0, $subOrders = [])
    {
        $groupOrder->append(['user']);
        //修改订单状态
        Db::transaction(function () use ($subOrders, $is_combine, $groupOrder) {
            $time = date('Y-m-d H:i:s');
            $groupOrder->paid = 1;
            $groupOrder->pay_time = $time;
            $groupOrder->is_combine = $is_combine;
            $orderStatus = [];
            $groupOrder->append(['orderList.orderProduct']);
            $flag = true;
            $finance = [];
            $profitsharing = [];
            $financialRecordRepository = app()->make(FinancialRecordRepository::class);
            $financeSn = $financialRecordRepository->getSn();
            $userMerchantRepository = app()->make(UserMerchantRepository::class);
            $storeOrderProfitsharingRepository = app()->make(StoreOrderProfitsharingRepository::class);
            $uid = $groupOrder->uid;
            $i = 1;
            $isVipCoupon = app()->make(StoreGroupOrderRepository::class)->isVipCoupon($groupOrder);
            //订单记录
            $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
            $svipDiscount = 0;
            $isPoints = false;
            foreach ($groupOrder->orderList as $_k => $order) {
                $order->paid = 1;
                $order->pay_time = $time;
                $svipDiscount = bcadd($order->svip_discount, $svipDiscount, 2);
                if (isset($subOrders[$order->order_sn])) {
                    $order->transaction_id = $subOrders[$order->order_sn]['transaction_id'];
                }
                $presell = false;
                //todo 等待付尾款
                if ($order->activity_type == 2) {
                    $_make = app()->make(ProductPresellSkuRepository::class);
                    if ($order->orderProduct[0]['cart_info']['productPresell']['presell_type'] == 2) {
                        $order->status = 10;
                        $presell = true;
                    } else {
                        $_make->incCount($order->orderProduct[0]['activity_id'], $order->orderProduct[0]['product_sku'], 'two_pay');
                    }
                    $_make->incCount($order->orderProduct[0]['activity_id'], $order->orderProduct[0]['product_sku'], 'one_pay');
                } else if ($order->activity_type == 4) {
                    $order->status = 9;
                    $order->save();
                    $group_buying_id = app()->make(ProductGroupBuyingRepository::class)->create(
                        $groupOrder->user,
                        $order->orderProduct[0]['cart_info']['activeSku']['product_group_id'],
                        $order->orderProduct[0]['activity_id'],
                        $order->order_id
                    );
                    $order->orderProduct[0]->activity_id = $group_buying_id;
                    $order->orderProduct[0]->save();
                } else if ($order->activity_type == 3) {
                    //更新助力状态
                    app()->make(ProductAssistSetRepository::class)->changStatus($order->orderProduct[0]['activity_id']);
                }
                if ($order->order_type == 1 && $order->status != 10)
                    $order->verify_code = $this->verifyCode();
                if ($order->orderProduct[0]->product->type == 2) {
                    $order->status = 2;
                    $order->delivery_type = 6;
                    $order->delivery_name = '自动发货';
                    $order->delivery_id = $this->sendCdkey($order);
                    $isPoints = true;
                }
                $order->save();
                if ($isPoints) $this->takeAfter($order, $groupOrder->user);
                $orderStatus[] = [
                    'order_id' => $order->order_id,
                    'order_sn' => $order->order_sn,
                    'type' => $storeOrderStatusRepository::TYPE_ORDER,
                    'change_message' => '订单支付成功',
                    'change_type' => $storeOrderStatusRepository::ORDER_STATUS_PAY_SUCCCESS,
                    'uid' => $order->uid,
                    'nickname' => $order->user->nickname,
                    'user_type' => $storeOrderStatusRepository::U_TYPE_USER,
                ];

                //TODO 成为推广员
                foreach ($order->orderProduct as $product) {
                    if ($flag && $product['cart_info']['product']['is_gift_bag']) {
                        app()->make(UserRepository::class)->promoter($order->uid);
                        $flag = false;
                    }
                }

                $finance[] = [
                    'order_id' => $order->order_id,
                    'order_sn' => $order->order_sn,
                    'user_info' => $groupOrder->user->nickname,
                    'user_id' => $uid,
                    'financial_type' => $presell ? 'order_presell' : 'order',
                    'financial_pm' => 1,
                    'type' => $presell ? 2 : 1,
                    'number' => $order->pay_price,
                    'mer_id' => $order->mer_id,
                    'financial_record_sn' => $financeSn . ($i++)
                ];

                $_payPrice = bcsub($order->pay_price, bcadd($order['extension_one'], $order['extension_two'], 3), 2);
                if ($presell) {
                    if (isset($order->orderProduct[0]['cart_info']['presell_extension_one']) && $order->orderProduct[0]['cart_info']['presell_extension_one'] > 0) {
                        $_payPrice = bcadd($_payPrice, $order->orderProduct[0]['cart_info']['presell_extension_one'], 2);
                    }
                    if (isset($order->orderProduct[0]['cart_info']['presell_extension_two']) && $order->orderProduct[0]['cart_info']['presell_extension_two'] > 0) {
                        $_payPrice = bcadd($_payPrice, $order->orderProduct[0]['cart_info']['presell_extension_two'], 2);
                    }
                }

                $_order_rate = 0;

                if ($order['commission_rate'] > 0) {

                    $commission_rate = ($order['commission_rate'] / 100);

                    $_order_rate = bcmul($_payPrice, $commission_rate, 2);

                    $_payPrice = bcsub($_payPrice, $_order_rate, 2);
                }

                if (!$presell) {
                    if ($order['extension_one'] > 0) {
                        $finance[] = [
                            'order_id' => $order->order_id,
                            'order_sn' => $order->order_sn,
                            'user_info' => $groupOrder->user->nickname,
                            'user_id' => $uid,
                            'financial_type' => 'brokerage_one',
                            'financial_pm' => 0,
                            'type' => 1,
                            'number' => $order['extension_one'],
                            'mer_id' => $order->mer_id,
                            'financial_record_sn' => $financeSn . ($i++)
                        ];
                    }

                    if ($order['extension_two'] > 0) {
                        $finance[] = [
                            'order_id' => $order->order_id,
                            'order_sn' => $order->order_sn,
                            'user_info' => $groupOrder->user->nickname,
                            'user_id' => $uid,
                            'financial_type' => 'brokerage_two',
                            'financial_pm' => 0,
                            'type' => 1,
                            'number' => $order['extension_two'],
                            'mer_id' => $order->mer_id,
                            'financial_record_sn' => $financeSn . ($i++)
                        ];
                    }
                    if ($order['commission_rate'] > 0) {
                        $finance[] = [
                            'order_id' => $order->order_id,
                            'order_sn' => $order->order_sn,
                            'user_info' => $groupOrder->user->nickname,
                            'user_id' => $uid,
                            'financial_type' => 'order_charge',
                            'financial_pm' => 0,
                            'type' => 1,
                            'number' => $_order_rate,
                            'mer_id' => $order->mer_id,
                            'financial_record_sn' => $financeSn . ($i++)
                        ];
                    }
                    $finance[] = [
                        'order_id' => $order->order_id,
                        'order_sn' => $order->order_sn,
                        'user_info' => $groupOrder->user->nickname,
                        'user_id' => $uid,
                        'financial_type' => $order['activity_type'] ==20  ? 'points_order_true' : 'order_true',
                        'financial_pm' => 0,
                        'type' => 2,
                        'number' => $_payPrice,
                        'mer_id' => $order->mer_id,
                        'financial_record_sn' => $financeSn . ($i++)
                    ];
                    if ($order->platform_coupon_price > 0) {
                        $finance[] = [
                            'order_id' => $order->order_id,
                            'order_sn' => $order->order_sn,
                            'user_info' => $groupOrder->user->nickname,
                            'user_id' => $uid,
                            'financial_type' => $isVipCoupon ? 'order_svip_coupon' : 'order_platform_coupon',
                            'financial_pm' => 0,
                            'type' => 1,
                            'number' => $order->platform_coupon_price,
                            'mer_id' => $order->mer_id,
                            'financial_record_sn' => $financeSn . ($i++)
                        ];
                        $_payPrice = bcadd($_payPrice, $order->platform_coupon_price, 2);
                    }
                    if (!$is_combine) {
                        app()->make(MerchantRepository::class)->addLockMoney($order->mer_id, 'order', $order->order_id, $_payPrice);
                    }
                }
                if ($is_combine) {
                    $profitsharing[] = [
                        'profitsharing_sn' => $storeOrderProfitsharingRepository->getOrderSn(),
                        'order_id' => $order->order_id,
                        'transaction_id' => $order->transaction_id ?? '',
                        'mer_id' => $order->mer_id,
                        'profitsharing_price' => $order->pay_price,
                        'profitsharing_mer_price' => $_payPrice,
                        'type' => $storeOrderProfitsharingRepository::PROFITSHARING_TYPE_ORDER,
                    ];
                }
                $userMerchantRepository->updatePayTime($uid, $order->mer_id, $order->pay_price);
                SwooleTaskService::merchant('notice', [
                    'type' => 'new_order',
                    'data' => [
                        'title' => '新订单',
                        'message' => '您有一个新的订单',
                        'id' => $order->order_id
                    ]
                ], $order->mer_id);
                //自动打印订单
                $this->autoPrinter($order->order_id, $order->mer_id);
            }
            if ($groupOrder->user->spread_uid) {
                Queue::push(UserBrokerageLevelJob::class, ['uid' => $groupOrder->user->spread_uid, 'type' => 'spread_pay_num', 'inc' => 1]);
                Queue::push(UserBrokerageLevelJob::class, ['uid' => $groupOrder->user->spread_uid, 'type' => 'spread_money', 'inc' => $groupOrder->pay_price]);
            }
            app()->make(UserRepository::class)->update($groupOrder->uid, [
                'pay_count' => Db::raw('pay_count+' . count($groupOrder->orderList)),
                'pay_price' => Db::raw('pay_price+' . $groupOrder->pay_price),
                'svip_save_money' => Db::raw('svip_save_money+' . $svipDiscount),
            ]);
            $this->giveIntegral($groupOrder);
            if (count($profitsharing)) {
                $storeOrderProfitsharingRepository->insertAll($profitsharing);
            }
            $financialRecordRepository->insertAll($finance);
            $storeOrderStatusRepository->batchCreateLog($orderStatus);
            if (count($groupOrder['give_coupon_ids']) > 0)
                $groupOrder['give_coupon_ids'] = app()->make(StoreCouponRepository::class)->getGiveCoupon($groupOrder['give_coupon_ids'])->column('coupon_id');
            $groupOrder->save();
        });
        if (count($groupOrder['give_coupon_ids']) > 0) {
            try {
                Queue::push(PayGiveCouponJob::class, ['ids' => $groupOrder['give_coupon_ids'], 'uid' => $groupOrder['uid']]);
            } catch (Exception $e) {
            }
        }

        Queue::push(SendSmsJob::class, ['tempId' => 'ORDER_PAY_SUCCESS', 'id' => $groupOrder->group_order_id]);
        Queue::push(SendSmsJob::class, ['tempId' => 'ADMIN_PAY_SUCCESS_CODE', 'id' => $groupOrder->group_order_id]);
        Queue::push(UserBrokerageLevelJob::class, ['uid' => $groupOrder->uid, 'type' => 'pay_money', 'inc' => $groupOrder->pay_price]);
        Queue::push(UserBrokerageLevelJob::class, ['uid' => $groupOrder->uid, 'type' => 'pay_num', 'inc' => 1]);
        app()->make(UserBrokerageRepository::class)->incMemberValue($groupOrder->uid, 'member_pay_num', $groupOrder->group_order_id);
        event('order.paySuccess', compact('groupOrder'));
    }

    /**
     * TODO 自动发货卡密商品
     * @param $order
     * @return mixed|string
     * @author Qinii
     * @day 2023/5/4
     */
    public function sendCdkey($order)
    {
        $productAttrValueRepository = app()->make(ProductAttrValueRepository::class);
        if (isset($order->orderProduct[0]['cart_info']['productAttr']['value_id'])){
            $where['value_id'] = $order->orderProduct[0]['cart_info']['productAttr']['value_id'];
        } else {
            $where['unique'] = $order->orderProduct[0]['cart_info']['productAttr']['unique'];
        }
        $rest = $productAttrValueRepository->getWhere($where,'*',['productCdkey' => function($query) {$query->where('status',1)->order('cdkey_id ASC')->withLimit(1);}]);
        $cdkey = $rest['productCdkey'][0];
        if ($cdkey['is_type']) {
            $msg = '卡号：'.$cdkey['key'];
            if ($cdkey['pwd']) $msg .= ';卡密：'.$cdkey['pwd'];
            $cdkey->status  = -1;
            $cdkey->save();
        } else {
            $msg = $cdkey['key'];
        }
        return $msg;
    }

    /**
     *  自动打印
     * @Author:Qinii
     * @Date: 2020/10/13
     * @param int $orderId
     * @param int $merId
     */
    public function autoPrinter(int $orderId, int $merId)
    {
        if (merchantConfig($merId, 'printing_auto_status')) {
            try {
                $this->batchPrinter($orderId, $merId);
            } catch (Exception $exception) {
                Log::info('自动打印小票报错：' . $exception);
            }
        } else {
            Log::info('自动打印小票验证：商户ID【' . $merId . '】，自动打印状态未开启');
        }
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/6/9
     */
    public function getNewOrderId($type)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $orderId = $type . $msectime . mt_rand(10000, max(intval($msec * 10000) + 10000, 98369));
        return $orderId;
    }

    /**
     * @param $cart
     * @return string
     * @author xaboy
     * @day 2020/6/9
     */
    public function productByTempNumber($cart)
    {
        $type = $cart['product']['temp']['type'];
        $cartNum = $cart['cart_num'];
        if (!$type)
            return $cartNum;
        else if ($type == 2) {
            return bcmul($cartNum, $cart['productAttr']['volume'], 2);
        } else {
            return bcmul($cartNum, $cart['productAttr']['weight'], 2);
        }
    }

    public function cartByPrice($cart)
    {
        if ($cart['product_type'] == '2') {
            return $cart['productPresellAttr']['presell_price'];
        } else if ($cart['product_type'] == '3') {
            return $cart['productAssistAttr']['assist_price'];
        } else if ($cart['product_type'] == '4') {
            return $cart['activeSku']['active_price'];
        } else {
            return $cart['productAttr']['price'];
        }
    }

    public function cartByCouponPrice($cart)
    {
        if ($cart['product_type'] == '2') {
            return $cart['productPresellAttr']['final_price'];
        } else if ($cart['product_type'] == '1') {
            return 0;
        } else if ($cart['product_type'] == '3') {
            return 0;
        } else if ($cart['product_type'] == '4') {
            return 0;
        } else {
            return $cart['productAttr']['price'];
        }
    }

    public function cartByDownPrice($cart)
    {
        if ($cart['product_type'] == '2') {
            return $cart['productPresellAttr']['down_price'];
        } else {
            return 0;
        }
    }


    /**
     * @param int $uid
     * @return array
     * @author xaboy
     * @day 2020/6/10
     */
    public function userOrderNumber(int $uid)
    {
        $noPay = app()->make(StoreGroupOrderRepository::class)->orderNumber($uid);
        $noPostage = $this->dao->search(['uid' => $uid, 'status' => 0, 'paid' => 1,'is_user' => 1])->where('StoreOrder.is_del', 0)->count();
        $all = $this->dao->search(['uid' => $uid, 'status' => -2,'is_user' => 1])->where('StoreOrder.is_del', 0)->count();
        $noDeliver = $this->dao->search(['uid' => $uid, 'status' => 1, 'paid' => 1])->where('StoreOrder.is_del', 0)->count();
        $noComment = $this->dao->search(['uid' => $uid, 'status' => 2, 'paid' => 1,'is_user' => 1])->where('StoreOrder.is_del', 0)->count();
        $done = $this->dao->search(['uid' => $uid, 'status' => 3, 'paid' => 1,'is_user' => 1])->where('StoreOrder.is_del', 0)->count();
        $refund = app()->make(StoreRefundOrderRepository::class)->getWhereCount(['uid' => $uid, 'status' => [0, 1, 2]]);
        //$orderPrice = $this->dao->search(['uid' => $uid, 'paid' => 1])->sum('pay_price');
        $orderCount = $this->dao->search(['uid' => $uid, 'paid' => 1,'is_user' => 1])->count();
        return compact('noComment', 'done', 'refund', 'noDeliver', 'noPay', 'noPostage', 'orderCount', 'all');
    }

    /**
     * @param $id
     * @param null $uid
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function getDetail($id, $uid = null)
    {
        $where = [];
        $with = [
            'orderProduct',
            'merchant' => function ($query) {
                return $query->field('mer_id,mer_name,service_phone')->append(['services_type']);
            },
            'receipt' => function ($query) {
                return $query->field('order_id,order_receipt_id');
            },
            'takeOrderList.orderProduct'
        ];
        if ($uid) {
            $where['uid'] = $uid;
        } else if (!$uid) {
            $with['user'] = function ($query) {
                return $query->field('uid,nickname');
            };
        }
        $order = $this->dao->search($where)->where('order_id', $id)->where('StoreOrder.is_del', 0)->with($with)->append(['refund_status','open_receipt'])->find();
        if (!$order) {
            return null;
        }
        if ($order->activity_type == 2) {
            if ($order->presellOrder) {
                $order->presellOrder->append(['activeStatus']);
                $order->presell_price = bcadd($order->pay_price, $order->presellOrder->pay_price, 2);
            } else {
                $order->presell_price = $order->pay_price;
            }
        }
        return $order;
    }

    public function codeByDetail($code, $uid = null)
    {
        $where = [];
        if ($uid) $where['uid'] = $uid;
        $data = $this->dao->search($where)->where('verify_code', $code)
            ->where('StoreOrder.is_del', 0)
            ->with([
                'orderProduct',
                'merchant' => function ($query) {
                    return $query->field('mer_id,mer_name');
                }
            ])
            ->find();
        if (!$data)
            throw new ValidateException('数据不存在');
        if ($data['status'])
            throw new ValidateException('该订单已全部核销');
        return $data;
    }

    public function giveIntegral($groupOrder)
    {
        if ($groupOrder->give_integral > 0) {
            app()->make(UserBillRepository::class)->incBill($groupOrder->uid, 'integral', 'lock', [
                'link_id' => $groupOrder['group_order_id'],
                'status' => 0,
                'title' => '下单赠送积分',
                'number' => $groupOrder->give_integral,
                'mark' => '成功消费' . floatval($groupOrder['pay_price']) . '元,赠送积分' . floatval($groupOrder->give_integral),
                'balance' => $groupOrder->user->integral
            ]);
        }
    }

    /**
     * @param StoreOrder $order
     * @param User $user
     * @author xaboy
     * @day 2020/8/3
     */
    public function computed(StoreOrder $order, User $user)
    {
        $userBillRepository = app()->make(UserBillRepository::class);
        if ($order->spread_uid) {
            $spreadUid = $order->spread_uid;
            $topUid = $order->top_uid;
        } else if ($order->is_selfbuy) {
            $spreadUid = $user->uid;
            $topUid = $user->spread_uid;
        } else {
            $spreadUid = $user->spread_uid;
            $topUid = $user->top_uid;
        }
        //TODO 添加冻结佣金
        if ($order->extension_one > 0 && $spreadUid) {
            $userBillRepository->incBill($spreadUid, 'brokerage', 'order_one', [
                'link_id' => $order['order_id'],
                'status' => 0,
                'title' => '获得推广佣金',
                'number' => $order->extension_one,
                'mark' => $user['nickname'] . '成功消费' . floatval($order['pay_price']) . '元,奖励推广佣金' . floatval($order->extension_one),
                'balance' => 0
            ]);
            $userRepository = app()->make(UserRepository::class);
            $userRepository->incBrokerage($spreadUid, $order->extension_one);
            //            app()->make(FinancialRecordRepository::class)->dec([
            //                'order_id' => $order->order_id,
            //                'order_sn' => $order->order_sn,
            //                'user_info' => $userRepository->getUsername($spreadUid),
            //                'user_id' => $spreadUid,
            //                'financial_type' => 'brokerage_one',
            //                'number' => $order->extension_one,
            //            ], $order->mer_id);
        }
        if ($order->extension_two > 0 && $topUid) {
            $userBillRepository->incBill($topUid, 'brokerage', 'order_two', [
                'link_id' => $order['order_id'],
                'status' => 0,
                'title' => '获得推广佣金',
                'number' => $order->extension_two,
                'mark' => $user['nickname'] . '成功消费' . floatval($order['pay_price']) . '元,奖励推广佣金' . floatval($order->extension_two),
                'balance' => 0
            ]);
            $userRepository = app()->make(UserRepository::class);
            $userRepository->incBrokerage($topUid, $order->extension_two);
            //            app()->make(FinancialRecordRepository::class)->dec([
            //                'order_id' => $order->order_id,
            //                'order_sn' => $order->order_sn,
            //                'user_info' => $userRepository->getUsername($topUid),
            //                'user_id' => $topUid,
            //                'financial_type' => 'brokerage_two',
            //                'number' => $order->extension_two,
            //            ], $order->mer_id);
        }
    }

    /**
     * @param StoreOrder $order
     * @param User $user
     * @param string $type
     * @author xaboy
     * @day 2020/8/3
     */
    public function takeAfter(StoreOrder $order, ?User $user)
    {
        Db::transaction(function () use ($user, $order) {
            if ($user) $this->computed($order, $user);
            Queue::push(SendSmsJob::class, ['tempId' => 'ORDER_TAKE_SUCCESS', 'id' => $order->order_id]);
            Queue::push(SendSmsJob::class, ['tempId' => 'ADMIN_TAKE_DELIVERY_CODE', 'id' => $order->order_id]);
            app()->make(MerchantRepository::class)->computedLockMoney($order);
            $order->save();
        });
    }

    /**
     * @param $id
     * @param User $user
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/17
     */
    public function takeOrder($id, ?User $user = null)
    {
        $order = $this->dao->searchAll(!$user ? [] : ['uid' => $user->uid])->where('order_id', $id)->where('StoreOrder.is_del', '<>',1)->find();
        if (!$order)
            throw new ValidateException('订单不存在');
        if ($order['status'] != 1 || ($order['order_type'] && $order['order_type'] != 20))
            throw new ValidateException('订单状态有误');
        $func = 'createUserLog';
        if (!$user){
            $func = 'createSysLog';
            $user = $order->user;
        }
//        if (!$user) {
//            throw new ValidateException('用户不存在');
//        }
        //订单状态（0：待发货；1：待收货；2：待评价；3：已完成； 9: 拼团中 10:  待付尾款 11:尾款超时未付 -1：已退款）
        $order->status = $order->activity_type == 20 ? 3 : 2;
        $order->verify_time = date('Y-m-d H:i:s');
        event('order.take.before', compact('order'));
        //订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'type' => $storeOrderStatusRepository::TYPE_ORDER,
            'change_message' => '已收货',
            'change_type' => $storeOrderStatusRepository::ORDER_STATUS_TAKE,
        ];
        Db::transaction(function () use ($order, $user,$storeOrderStatusRepository,$orderStatus,$func) {
            $this->takeAfter($order, $user);
            $order->save();
            $storeOrderStatusRepository->{$func}($orderStatus);
        });
        event('order.take', compact('order'));
    }


    /**
     *  获取订单列表头部统计数据
     * @Author:Qinii
     * @Date: 2020/9/12
     * @param int|null $merId
     * @param int|null $orderType
     * @return array
     */
    public function OrderTitleNumber(?int $merId, ?int $orderType)
    {
        $where = [];
        $sysDel = $merId ? 0 : null;                    //商户删除
        if ($merId) $where['mer_id'] = $merId;          //商户订单
        if ($orderType === 0) $where['order_type'] = 0; //普通订单
        if ($orderType === 1) $where['take_order'] = 1; //已核销订单
        //1: 未支付 2: 未发货 3: 待收货 4: 待评价 5: 交易完成 6: 已退款 7: 已删除
        $all = $this->dao->search($where, $sysDel)->where($this->getOrderType(0))->count();
        $statusAll = $all;
        $unpaid = $this->dao->search($where, $sysDel)->where($this->getOrderType(1))->count();
        $unshipped = $this->dao->search($where, $sysDel)->where($this->getOrderType(2))->count();
        $untake = $this->dao->search($where, $sysDel)->where($this->getOrderType(3))->count();
        $unevaluate = $this->dao->search($where, $sysDel)->where($this->getOrderType(4))->count();
        $complete = $this->dao->search($where, $sysDel)->where($this->getOrderType(5))->count();
        $refund = $this->dao->search($where, $sysDel)->where($this->getOrderType(6))->count();
        $del = $this->dao->search($where, $sysDel)->where($this->getOrderType(7))->count();

        return compact('all', 'statusAll', 'unpaid', 'unshipped', 'untake', 'unevaluate', 'complete', 'refund', 'del');
    }

    public function orderType(array $where)
    {
        return [
            [
                'count' => $this->dao->search($where)->count(),
                'title' => '全部',
                'order_type' => -1,
            ],
            [
                'count' => $this->dao->search($where)->where('order_type', 0)->where('is_virtual', 0)->count(),
                'title' => '普通订单',
                'order_type' => 0,
            ],
            [
                'count' => $this->dao->search($where)->where('order_type', 1)->count(),
                'title' => '核销订单',
                'order_type' => 1,
            ],
            [
                'count' => $this->dao->search($where)->where('is_virtual', 1)->count(),
                'title' => '虚拟商品订单',
                'order_type' => 2,
            ],
            [
                'count' => $this->dao->search(array_merge($where,['order_type' => 3]))->count(),
                'title' => '卡密商品订单',
                'order_type' => 3,
            ],
        ];
    }

    /**
     * @param $status
     * @return mixed
     * @author Qinii
     */
    public function getOrderType($status)
    {
        $param['StoreOrder.is_del'] = 0;
        switch ($status) {
            case 1:
                $param['paid'] = 0;
                break;    // 未支付
            case 2:
                $param['paid'] = 1;
                $param['StoreOrder.status'] = 0;
                break;  // 待发货
            case 3:
                $param['StoreOrder.status'] = 1;
                break;  // 待收货
            case 4:
                $param['StoreOrder.status'] = 2;
                break;  // 待评价
            case 5:
                $param['StoreOrder.status'] = 3;
                break;  // 交易完成
            case 6:
                $param['StoreOrder.status'] = -1;
                break;  // 已退款
            case 7:
                $param['StoreOrder.is_del'] = 1;
                break;  // 待核销
                break;  // 已删除
            default:
                unset($param['StoreOrder.is_del']);
                break;  //全部
        }
        return $param;
    }

    /**
     * @param int $id
     * @param int|null $merId
     * @return array|Model|null
     * @author Qinii
     */
    public function merDeliveryExists(int $id, ?int $merId, ?int $re = 0)
    {
        $where = ['order_id' => $id, 'is_del' => 0, 'paid' => 1];
        if ($re) $where['status'] = 0;
        if ($merId) $where['mer_id'] = $merId;
        return $this->dao->merFieldExists($where);
    }

    /**
     * TODO
     * @param int $id
     * @param int|null $merId
     * @return bool
     * @author Qinii
     * @day 2020-06-11
     */
    public function merGetDeliveryExists(int $id, ?int $merId)
    {
        $where = ['order_id' => $id, 'is_del' => 0, 'paid' => 1, 'status' => 1];
        if ($merId) $where['mer_id'] = $merId;
        return $this->dao->merFieldExists($where);
    }

    /**
     * @param int $id
     * @param int|null $merId
     * @return array|Model|null
     * @author Qinii
     */
    public function merStatusExists(int $id, ?int $merId)
    {
        $where = ['order_id' => $id, 'is_del' => 0, 'paid' => 0, 'status' => 0];
        if ($merId) $where['mer_id'] = $merId;
        return $this->dao->merFieldExists($where);
    }

    public function userDelExists(int $id, ?int $merId)
    {
        $where = ['order_id' => $id, 'is_del' => 1];
        if ($merId) $where['mer_id'] = $merId;
        return $this->dao->merFieldExists($where);
    }

    /**
     * @param $id
     * @return Form
     * @author Qinii
     */
    public function form($id)
    {
        $data = $this->dao->getWhere([$this->dao->getPk() => $id], 'total_price,pay_price,total_postage,pay_postage');
        $form = Elm::createForm(Route::buildUrl('merchantStoreOrderUpdate', ['id' => $id])->build());
        $form->setRule([
            Elm::number('total_price', '订单总价', $data['total_price'])->required(),
            Elm::number('total_postage', '订单邮费', $data['total_postage'])->required(),
            Elm::number('pay_price', '实际支付金额', $data['pay_price'])->required(),
        ]);
        return $form->setTitle('修改订单');
    }

    /**
     * TODO 修改订单价格
     * @param int $id
     * @param array $data
     * @author Qinii
     * @day 12/15/20
     */
    public function eidt(int $id, array $data, $service_id = 0)
    {

        /**
         * 1 计算出新的实际支付价格
         *      1.1 计算邮费
         *      1.2 计算商品总价
         * 2 修改订单信息
         * 3 计算总单数据
         * 4 修改总单数据
         * 5 修改订单商品单价
         *
         * pay_price = total_price - coupon_price + pay_postage
         */
        $order = $this->dao->get($id);
        if ($order->activity_type == 2) {
            throw new ValidateException('预售订单不支持改价');
        }
        $extension_total = (float)bcadd($order->extension_one, $order->extension_two, 2);
        $data['pay_price'] = $this->bcmathPrice($data['total_price'], $order['coupon_price'], $data['pay_postage']);
        if ($data['pay_price'] < 0) {
            throw new ValidateException('实际支付金额不能小于0');
        } else if ($data['pay_price'] < $extension_total) {
            throw new ValidateException('实际支付金额不能小于佣金' . $extension_total);
        }
        $make = app()->make(StoreGroupOrderRepository::class);
        $orderGroup = $make->dao->getWhere(['group_order_id' => $order['group_order_id']]);

        //总单总价格
        $_group['total_price'] = $this->bcmathPrice($orderGroup['total_price'], $order['total_price'], $data['total_price']);
        //总单实际支付价格
        $_group['pay_price'] = $this->bcmathPrice($orderGroup['pay_price'], $order['pay_price'], $data['pay_price']);
        //总单实际支付邮费
        $_group['pay_postage'] = $this->bcmathPrice($orderGroup['pay_postage'], $order['pay_postage'], $data['pay_postage']);
        event('order.changePrice.before', compact('order', 'data'));
        //订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);

        $orderStatus = [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'type' => $storeOrderStatusRepository::TYPE_ORDER,
            'change_message' => '订单价格:'.$order['pay_price'].'修改为:'.$data['pay_price'],
            'change_type' => $storeOrderStatusRepository::ORDER_STATUS_CHANGE,
        ];

        Db::transaction(function () use ($id, $data, $orderGroup, $order, $_group,$storeOrderStatusRepository,$orderStatus,$service_id) {
            $orderGroup->total_price = $_group['total_price'];
            $orderGroup->pay_price = $_group['pay_price'];
            $orderGroup->pay_postage = $_group['pay_postage'];
            $orderGroup->group_order_sn = $this->getNewOrderId(StoreOrderRepository::TYPE_SN_ORDER) . '0';
            $orderGroup->save();

            $this->dao->update($id, $data);
            $this->changOrderProduct($id, $data);

            if ($service_id) {
                $storeOrderStatusRepository->createServiceLog($service_id,$orderStatus);
            } else {
                $storeOrderStatusRepository->createAdminLog($orderStatus);
            }
            if ($data['pay_price'] != $order['pay_price']) Queue::push(SendSmsJob::class, ['tempId' => 'PRICE_REVISION_CODE', 'id' => $id]);
        });
        event('order.changePrice', compact('order', 'data'));
    }

    /**
     * TODO 改价后重新计算每个商品的单价
     * @param int $orderId
     * @param array $data
     * @author Qinii
     * @day 12/15/20
     */
    public function changOrderProduct(int $orderId, array $data)
    {
        $make = app()->make(StoreOrderProductRepository::class);
        $ret = $make->getSearch(['order_id' => $orderId])->field('order_product_id,product_num,product_price')->select();
        $count = $make->getSearch(['order_id' => $orderId])->sum('product_price');
        $_count = (count($ret->toArray()) - 1);
        $pay_price = $data['total_price'];
        foreach ($ret as $k => $item) {
            $_price = 0;
            /**
             *  比例 =  单个商品总价 / 订单原总价；
             *
             *  新的商品总价 = 比例 * 订单修改总价
             *
             *  更新数据库
             */
            if ($k == $_count) {
                $_price = $pay_price;
            } else {
                $_reta = bcdiv($item->product_price, $count, 3);
                $_price = bcmul($_reta, $data['total_price'], 2);
            }

            $item->product_price = $_price;
            $item->save();

            $pay_price = $this->bcmathPrice($pay_price, $_price, 0);
        }
    }

    /**
     * TODO 计算的重复利用
     * @param $total
     * @param $old
     * @param $new
     * @return int|string
     * @author Qinii
     * @day 12/15/20
     */
    public function bcmathPrice($total, $old, $new)
    {
        $_bcsub = bcsub($total, $old, 2);
        $_count = (bccomp($_bcsub, 0, 2) == -1) ? 0 : $_bcsub;
        $count = bcadd($_count, $new, 2);
        return (bccomp($count, 0, 2) == -1) ? 0 : $count;
    }

    /**
     * @param $id
     * @param $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/12
     */
    public function refundProduct($id, $uid)
    {
        $order = $this->dao->userOrder($id, $uid);
        if (!$order)
            throw new ValidateException('订单不存在');
        if (!count($order->refundProduct))
            throw new ValidateException('没有可退款商品');
        return $order->refundProduct->toArray();
    }

    /**
     * TODO
     * @param $id
     * @param $data
     * @return mixed
     * @author Qinii
     * @day 7/26/21
     */
    public function orderDumpInfo($id, $data, $merId)
    {

        if (!$data['temp_id'])
            throw new ValidateException('请填写模板ID');
        if (!$data['from_name'])
            throw new ValidateException('请填写发货人姓名');
        if (!$data['from_tel'])
            throw new ValidateException('请填写发货电话');
        if (!$data['from_addr'])
            throw new ValidateException('请填写发货地址');
        $orderInfo = $this->dao->getWhere(['order_id' => $id]);
        if ($orderInfo['is_virtual'])
            throw new ValidateException('虚拟商品只能虚拟发货');
        $cargo = '';
        $count = 0;
        $weight = 0;
        foreach ($orderInfo->orderProduct as $item) {
            $cargo .= mb_substr($item['cart_info']['product']['store_name'],0,10). ' ' .$item['cart_info']['productAttr']['sku']  .' * ' .$item['product_num'].$item['cart_info']['product']['unit_name'].PHP_EOL;
            $count += $item['product_num'];
            $weight += $item['cart_info']['productAttr']['weight'];
        }
        $expData['com'] = $data['com'];
        $expData['to_name'] = $orderInfo->real_name;
        $expData['to_tel'] = $orderInfo->user_phone;
        $expData['to_addr'] = $orderInfo->user_address;
        $expData['from_name'] = $data['from_name'];
        $expData['from_tel'] = $data['from_tel'];
        $expData['from_addr'] = $data['from_addr'];
        $expData['siid'] = merchantConfig($merId,'mer_config_siid');
        $expData['temp_id'] = $data['temp_id'];
        $expData['count'] = $count;
        $expData['weight'] = $weight;
        $expData['cargo'] = $cargo;
        $expData['order_id'] = $orderInfo->order_id;
        return $expData;
    }

    /**
     * TODO 批量发货
     * @param int $merId
     * @param array $params
     * @author Qinii
     * @day 7/26/21
     */
    public function batchDelivery(int $merId, array $params)
    {
        $count = count($params['order_id']);
        $import = app()->make(StoreImportRepository::class)->create($merId, 'delivery', $params['delivery_type']);
        $make = app()->make(StoreImportDeliveryRepository::class);
        $data = [];
        $num = 0;
        foreach ($params['order_id'] as $item) {
            $ret = $this->dao->getWhere(['order_id' => $params['order_id']]);
            $imp = [
                'order_sn' => $ret['order_sn'] ?? $item,
                'delivery_id' => $params['delivery_id'],
                'delivery_type' => $params['delivery_type'],
                'delivery_name' => $params['delivery_name'],
                'import_id' => $import['import_id'],
                'mer_id' => $merId
            ];
            if (!$ret  || $ret['mer_id'] != $merId || $ret['is_del'] != 0 || $ret['paid'] != 1 || $ret['delivery_type']) {
                $imp['status'] = 0;
                $imp['mark'] = '订单信息不存在或状态错误';
            } else {
                try {
                    if ($params['delivery_type'] == 4) {
                        $dump = [
                            'temp_id' => $params['temp_id'],
                            'from_tel' => $params['from_tel'],
                            'from_addr' => $params['from_addr'],
                            'from_name' => $params['from_name'],
                            'delivery_name' => $params['delivery_name'],
                        ];
                        $dump = $this->orderDumpInfo($item, $dump, $merId);
                        $ret = $this->dump($item, $merId, $dump);
                        $imp['delivery_id'] = $ret['delivery_id'];
                        $imp['delivery_name'] = $ret['delivery_name'];
                    } else {
                        $this->delivery($item, $merId,[
                            'delivery_id' => $params['delivery_id'],
                            'delivery_type' => $params['delivery_type'],
                            'delivery_name' => $params['delivery_name'],
                        ]);
                    }
                    $num++;
                    $imp['status'] = 1;
                } catch (Exception $exception) {
                    $imp['status'] = 0;
                    $imp['mark'] = $exception->getMessage();
                }
            }
            $data[] = $imp;
        }

        $_status = ($num == 0) ? -1 : (($count == $num) ? 1 : 10);
        $make->insertAll($data);
        $arr = ['count' => $count, 'success' => $num, 'status' => $_status];
        app()->make(StoreImportRepository::class)->update($import['import_id'], $arr);
    }


    /**
     * TODO 打印电子面单，组合参数
     * @param int $id
     * @param int $merId
     * @param array $data
     * @return mixed
     * @author Qinii
     * @day 7/26/21
     */
    public function dump(int $id, int $merId, array $data, $service_id = 0)
    {
        $make = app()->make(MerchantRepository::class);
        $make->checkCrmebNum($merId, 'dump');
        $data['com'] = $data['delivery_name'];
        $data = $this->orderDumpInfo($id, $data, $merId);
        $result = app()->make(CrmebServeServices::class)->express()->dump($merId, $data);
        if (!isset($result['kuaidinum'])) throw new ValidateException('打印失败');
        $delivery = [
            'delivery_type' => 4,
            'delivery_name' => $data['com'],
            'delivery_id' => $result['kuaidinum'],
            'remark' => $data['remark'] ?? '',
            'kuaidi_label' => $result['label'] ?? '',
        ];
        $dump = [
            'delivery_name' => $delivery['delivery_name'],
            'delivery_id' => $delivery['delivery_id'],
            'from_name' => $data['from_name'],
            'order_id' => $data['order_id'],
            'to_name' => $data['to_name'],
        ];
        Db::transaction(function () use ($merId, $id, $delivery, $make, $dump, $service_id) {
            $this->delivery($id, $merId, $delivery,$service_id);
            $arr = [
                'type' => 'mer_dump',
                'num' => -1,
                'message' => '电子面单',
                'info' => $dump
            ];
            app()->make(ProductCopyRepository::class)->add($arr, $merId);
        });
        return $result;
    }

    public function runDelivery($id, $merId, $data, $split, $method,$service_id = 0)
    {
        return Db::transaction(function () use ($id, $merId, $data, $split, $method,$service_id) {
            if ($split['is_split'] && !empty($split['split'])) {
                foreach ($split['split'] as $v) {
                    $splitData[$v['id']] = $v['num'];
                }
                $order = $this->dao->get($id);
                $newOrder = app()->make(StoreOrderSplitRepository::class)->splitOrder($order, $splitData,$service_id);
                if ($newOrder){
                    $id = $newOrder->order_id;
                } else {
                    throw new ValidateException('商品不能全部拆单');
                }
            }
            return $this->{$method}($id, $merId, $data, $service_id);
        });
    }

    /**
     * TODO 发货订单操作
     * @param $id
     * @param $data
     * @return mixed
     * @author Qinii
     * @day 7/26/21
     */
    public function delivery($id, $merId, $data, $service_id = 0)
    {
        $data['status'] = 1;
        $order = $this->dao->get($id);
        if ($order['is_virtual'] == 1 && $data['delivery_type'] != 3)
            throw new ValidateException('虚拟商品只能虚拟发货');
        //订单记录
        $statusRepository = app()->make(StoreOrderStatusRepository::class);
        switch ($data['delivery_type']) {
            case 1:
                $exprss = app()->make(ExpressRepository::class)->getWhere(['code' => $data['delivery_name']]);
                if (!$exprss) throw new ValidateException('快递公司不存在');
                $data['delivery_name'] = $exprss['name'];
                $change_type = $statusRepository::ORDER_DELIVERY_COURIER;
                $change_message = '订单已配送【快递名称】:' . $exprss['name'] . '; 【快递单号】：' . $data['delivery_id'];
                $temp_code = 'DELIVER_GOODS_CODE';
                break;
            case 2:
                if (!preg_match("/^1[3456789]{1}\d{9}$/", $data['delivery_id'])) throw new ValidateException('手机号格式错误');
                $change_type = 'delivery_1';
                $change_message = '订单已配送【送货人姓名】:' . $data['delivery_name'] . '; 【手机号】：' . $data['delivery_id'];
                $temp_code = 'ORDER_DELIVER_SUCCESS';
                break;
            case 3:
                $change_type = $statusRepository::ORDER_DELIVERY_NOTHING;
                $change_message = '订单已配送【虚拟发货】';
                $data['status'] = 2;
                break;
            case 4:
                $exprss = app()->make(ExpressRepository::class)->getWhere(['code' => $data['delivery_name']]);
                if (!$exprss) throw new ValidateException('快递公司不存在');
                $data['delivery_name'] = $exprss['name'];
                $change_type = $statusRepository::ORDER_DELIVERY_COURIER;
                $change_message = '订单已配送【快递名称】:' . $exprss['name'] . '; 【快递单号】：' . $data['delivery_id'];
                $temp_code = 'DELIVER_GOODS_CODE';
                break;
        }

        event('order.delivery.before', compact('order', 'data'));
        $this->dao->update($id, $data);

        $orderStatus = [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'type' => $statusRepository::TYPE_ORDER,
            'change_message' => $change_message,
            'change_type' => $change_type,
        ];
        if ($service_id) {
            $statusRepository->createServiceLog($service_id,$orderStatus);
        } else {
            $statusRepository->createAdminLog($orderStatus);
        }

        //虚拟发货后用户直接确认收获
        if($data['status'] ==  2){
            $user = app()->make(UserRepository::class)->get($order['uid']);
            //订单记录
            $this->takeAfter($order,$user);
            $orderStatus = [
                'order_id' => $order->order_id,
                'order_sn' => $order->order_sn,
                'type' => $statusRepository::TYPE_ORDER,
                'change_message' => '虚拟发货后',
                'change_type' => $statusRepository::ORDER_STATUS_TAKE,
            ];
            $statusRepository->createSysLog($orderStatus);

        }
        if (isset($temp_code)) Queue::push(SendSmsJob::class, ['tempId' => $temp_code, 'id' => $order->order_id]);

        event('order.delivery', compact('order', 'data'));
        return $data;
    }

    /**
     * TODO 同城配送
     * @param int $id
     * @param int $merId
     * @param array $data
     * @author Qinii
     * @day 2/16/22
     */
    public function cityDelivery(int $id, int $merId, array $data, $service_id)
    {
        $make = app()->make(DeliveryOrderRepository::class);
        $order = $this->dao->get($id);
        if ($order['is_virtual'])
            throw new ValidateException('虚拟商品只能虚拟发货');
        $make->create($id, $merId, $data, $order);
        //订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $this->dao->update($id, ['delivery_type' => 5, 'status' => 1,'remark' => $data['remark']]);

        $orderStatus = [
            'order_id' => $id,
            'order_sn' => $order->order_sn,
            'type' => $storeOrderStatusRepository::TYPE_ORDER,
            'change_message' => '订单配送【同城配送】',
            'change_type' => $storeOrderStatusRepository::ORDER_DELIVERY_SELF,
        ];
        if ($service_id) {
            $storeOrderStatusRepository->createServiceLog($service_id,$orderStatus);
        } else {
            $storeOrderStatusRepository->createAdminLog($orderStatus);
        }

        Queue::push(SendSmsJob::class, ['tempId' => 'ORDER_DELIVER_SUCCESS', 'id' => $id]);
    }


    public function getOne($id, ?int $merId)
    {
        $where = [$this->getPk() => $id];
        if ($merId) {
            $whre['mer_id'] = $merId;
            $whre['is_system_del'] = 0;
        }
        $res = $this->dao->getWhere($where, '*', [
            'orderProduct',
            'user' => function ($query) {
                $query->field('uid,real_name,nickname,is_svip,svip_endtime,phone');
            },
            'refundOrder' => function ($query) {
                $query->field('order_id,extension_one,extension_two,refund_price,integral')->where('status', 3);
            },
            'finalOrder',
            'TopSpread' => function ($query) {
                $query->field('uid,nickname,avatar');
            },
            'spread' => function ($query) {
                $query->field('uid,nickname,avatar');
            },
            ]
        );
        if (!$res) throw new ValidateException('数据不存在');
        $res['integral'] = (int)$res['integral'];
        return $res->append(['refund_extension_one', 'refund_extension_two']);
    }

    public function getOrderStatus($where, $page, $limit)
    {
        $where['type'] = StoreOrderStatusRepository::TYPE_ORDER;
        return app()->make(StoreOrderStatusRepository::class)->search($where, $page, $limit);
    }

    public function remarkForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('merchantStoreOrderRemark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('remark', '备注', $data['remark'])->required(),
        ]);
        return $form->setTitle('订单备注');
    }

    public function adminMarkForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('systemMerchantOrderMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('admin_mark', '备注', $data['admin_mark'])->required(),
        ]);
        return $form->setTitle('订单备注');
    }

    public function pointsMarkForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('pointsOrderMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('remark', '备注', $data['remark'])->required(),
        ]);
        return $form->setTitle('订单备注');
    }


    /**
     * TODO 平台每个商户的订单列表
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-15
     */
    public function adminMerGetList($where, $page, $limit)
    {
        $where['paid'] = 1;
        $query = $this->dao->search($where, null);
        $count = $query->count();
        $list = $query->with([
            'orderProduct',
            'merchant' => function ($query) {
                $query->field('mer_id,mer_name,is_trader');
            },
            'groupOrder' => function ($query) {
                $query->field('group_order_id,group_order_sn');
            },
            'finalOrder',
            'user' => function ($query) {
                $query->field('uid,nickname,avatar');
            },
        ])->page($page, $limit)->select()->append(['refund_extension_one', 'refund_extension_two']);

        return compact('count', 'list');
    }

    public function reconList($where, $page, $limit)
    {
        $ids = app()->make(MerchantReconciliationOrderRepository::class)->getIds($where);
        $query = $this->dao->search([], null)->whereIn('order_id', $ids);
        $count = $query->count();
        $list = $query->with(['orderProduct'])->page($page, $limit)->select()->each(function ($item) {
            //(实付金额 - 一级佣金 - 二级佣金) * 抽成
            $commission_rate = ($item['commission_rate'] / 100);
            //佣金
            $_order_extension = bcadd($item['extension_one'], $item['extension_two'], 3);
            //手续费 =  (实付金额 - 一级佣金 - 二级佣金) * 比例
            $_order_rate = bcmul(bcsub($item['pay_price'], $_order_extension, 3), $commission_rate, 3);
            $item['order_extension'] = round($_order_extension, 2);
            $item['order_rate'] = round($_order_rate, 2);
            return $item;
        });

        return compact('count', 'list');
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     */
    public function merchantGetList(array $where, $page, $limit)
    {
        $status = $where['status'];
        unset($where['status']);
        $query = $this->dao->search($where)->where($this->getOrderType($status))
            ->with([
                'orderProduct',
                'merchant' => function ($query) {
                    $query->field('mer_id,mer_name');
                },
                'verifyService' => function ($query) {
                    $query->field('service_id,nickname');
                },
                'finalOrder',
                'groupUser.groupBuying',
                'TopSpread' => function ($query) {
                    $query->field('uid,nickname,avatar');
                },
                'spread' => function ($query) {
                    $query->field('uid,nickname,avatar');
                },
            ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->append(['refund_extension_one', 'refund_extension_two'])
            ->each(function($item){
                // 1:退款中 2:部分退款 3 = 全退
                $refunding = 0;
                if ($item['orderProduct']) {
                    $is_refund = array_column($item['orderProduct']->toArray(),'is_refund');
                    $is_refund = array_unique($is_refund);
                    if (in_array(1,$is_refund)) {
                        $refunding = 1;
                    } else if (in_array(2,$is_refund)) {
                        $refunding = 2;
                    } else if (in_array(3,$is_refund)) {
                        $refunding = 3;
                    }
                }
                $item['refunding'] = $refunding;
            });


        return compact('count', 'list');
    }

    /**
     * TODO 平台总的订单列表
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-15
     */
    public function adminGetList(array $where, $page, $limit)
    {
        $status = $where['status'];
        unset($where['status']);
        $query = $this->dao->search($where, null)->where($this->getOrderType($status))
            ->with([
                'orderProduct',
                'merchant' => function ($query) {
                    return $query->field('mer_id,mer_name,is_trader');
                },
                'verifyService' => function ($query) {
                    return $query->field('service_id,nickname');
                },
                'groupOrder' => function ($query) {
                    $query->field('group_order_id,group_order_sn');
                },
                'finalOrder',
                'groupUser.groupBuying',
                'TopSpread' => function ($query) {
                    $query->field('uid,nickname,avatar');
                },
                'spread' => function ($query) {
                    $query->field('uid,nickname,avatar');
                },
                'user' => function ($query) {
                    $query->field('uid,nickname,avatar');
                },
            ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select()->append(['refund_extension_one', 'refund_extension_two']);

        return compact('count', 'list');
    }

    public function getStat(array $where, $status)
    {
        unset($where['status']);
        $make = app()->make(StoreRefundOrderRepository::class);
        $presellOrderRepository = app()->make(PresellOrderRepository::class);

        //退款订单id
        $orderId = $this->dao->search($where)->where($this->getOrderType($status))->column('order_id');
        //退款金额
        $orderRefund = $make->refundPirceByOrder($orderId);
        //实际支付订单数量
        $all = $this->dao->search($where)->where($this->getOrderType($status))->where('paid', 1)->count();
        //实际支付订单金额
        $countQuery = $this->dao->search($where)->where($this->getOrderType($status))->where('paid', 1);
        $countOrderId = $countQuery->column('order_id');
        $countPay1 = $countQuery->sum('StoreOrder.pay_price');
        $countPay2 = $presellOrderRepository->search(['paid' => 1, 'order_ids' => $countOrderId])->sum('pay_price');
        $countPay = bcadd($countPay1, $countPay2, 2);

        //余额支付
        $banclQuery = $this->dao->search(array_merge($where, ['paid' => 1, 'pay_type' => 0]))->where($this->getOrderType($status));
        $banclOrderId = $banclQuery->column('order_id');
        $banclPay1 = $banclQuery->sum('StoreOrder.pay_price');
        $banclPay2 = $presellOrderRepository->search(['pay_type' => [0], 'paid' => 1, 'order_ids' => $banclOrderId])->sum('pay_price');
        $banclPay = bcadd($banclPay1, $banclPay2, 2);

        //微信金额
        $wechatQuery = $this->dao->search($where)->where($this->getOrderType($status))->where('paid', 1)->where('pay_type', 'in', [1, 2, 3, 6]);
        $wechatOrderId = $wechatQuery->column('order_id');
        $wechatPay1 = $wechatQuery->sum('StoreOrder.pay_price');
        $wechatPay2 = $presellOrderRepository->search(['pay_type' => [1, 2, 3, 6], 'paid' => 1, 'order_ids' => $wechatOrderId])->sum('pay_price');
        $wechatPay = bcadd($wechatPay1, $wechatPay2, 2);

        //支付宝金额
        $aliQuery = $this->dao->search($where)->where($this->getOrderType($status))->where('paid', 1)->where('pay_type', 'in', [4, 5]);
        $aliOrderId = $aliQuery->column('order_id');
        $aliPay1 = $aliQuery->sum('StoreOrder.pay_price');
        $aliPay2 = $presellOrderRepository->search(['pay_type' => [4, 5], 'paid' => 1, 'order_ids' => $aliOrderId])->sum('pay_price');
        $aliPay = bcadd($aliPay1, $aliPay2, 2);


        $stat = [
            [
                'className' => 'el-icon-s-goods',
                'count' => $all,
                'field' => '件',
                'name' => '已支付订单数量'
            ],
            [
                'className' => 'el-icon-s-order',
                'count' => (float)$countPay,
                'field' => '元',
                'name' => '实际支付金额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => (float)$orderRefund,
                'field' => '元',
                'name' => '已退款金额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => (float)$wechatPay,
                'field' => '元',
                'name' => '微信支付金额'
            ],
            [
                'className' => 'el-icon-s-finance',
                'count' => (float)$banclPay,
                'field' => '元',
                'name' => '余额支付金额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => (float)$aliPay,
                'field' => '元',
                'name' => '支付宝支付金额'
            ],
        ];
        return $stat;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->where('StoreOrder.is_del', 0);
        $count = $query->count();
        $list = $query->with([
            'orderProduct',
            'presellOrder',
            'merchant' => function ($query) {
                return $query->field('mer_id,mer_name');
            },
            'community',
            'receipt' => function ($query) {
                return $query->field('order_id,order_receipt_id');
            },
        ])->page($page, $limit)->order('pay_time DESC')->append(['refund_status','open_receipt'])->select();

        foreach ($list as $order) {
            if ($order->activity_type == 2) {
                if ($order->presellOrder) {
                    $order->presellOrder->append(['activeStatus']);
                    $order->presell_price = bcadd($order->pay_price, $order->presellOrder->pay_price, 2);
                } else {
                    $order->presell_price = $order->pay_price;
                }
            }
            $order->takeOrderCount = count($order['takeOrderList']);
            unset($order['takeOrderList']);
        }

        return compact( 'count','list');
    }

    public function userList($uid, $page, $limit)
    {
        $query = $this->dao->search([
            'uid' => $uid,
            'paid' => 1
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }


    public function userMerList($uid, $merId, $page, $limit)
    {
        $query = $this->dao->search([
            'uid' => $uid,
            'mer_id' => $merId,
            'paid' => 1
        ]);
        $count = $query->count();
        $list = $query->with(['presellOrder'])->page($page, $limit)->select();
        foreach ($list as $order) {
            if ($order->activity_type == 2 && $order->status >= 0 && $order->status < 10 && $order->presellOrder) {
                $order->pay_price = bcadd($order->pay_price, $order->presellOrder->pay_price, 2);
            }
        }
        return compact('count', 'list');
    }

    public function express(int $orderId, ?int $merId)
    {
        $order = $this->dao->get($orderId);
        if ($merId && $order['mer_id'] != $merId) throw new ValidateException('订单信息不存在');
        if (!in_array($order['delivery_type'], [1, 4])) throw new ValidateException('订单状态错误');
        return ExpressService::express($order->delivery_id, $order->delivery_name, $order->user_phone);
    }

    public function checkPrinterConfig(int $merId)
    {
        if (!merchantConfig($merId, 'printing_status'))
            throw new ValidateException('打印功能未开启');
        $config = [
            'clientId' => merchantConfig($merId, 'printing_client_id'),
            'apiKey' => merchantConfig($merId, 'printing_api_key'),
            'partner' => merchantConfig($merId, 'develop_id'),
            'terminal' => merchantConfig($merId, 'terminal_number')
        ];
        if (!$config['clientId'] || !$config['apiKey'] || !$config['partner'] || !$config['terminal'])
            throw new ValidateException('打印机配置错误');
        return $config;
    }

    /**
     * TODO 打印机 -- 暂无使用
     * @param int $id
     * @param int $merId
     * @return bool|mixed|string
     * @author Qinii
     * @day 2020-07-30
     */
    public function printer(int $id, int $merId)
    {
        $order = $this->dao->getWhere(['order_id' => $id], '*', ['orderProduct', 'merchant' => function ($query) {
            $query->field('mer_id,mer_name');
        }]);
        foreach ($order['orderProduct'] as $item) {
            $product[] = [
                'store_name' => $item['cart_info']['product']['store_name'] . '【' . $item['cart_info']['productAttr']['sku'] . '】',
                'product_num' => $item['product_num'],
                'price' => bcdiv($item['product_price'], $item['product_num'], 2),
                'product_price' => $item['product_price'],
            ];
        }
        $data = [
            'order_sn' => $order['order_sn'],
            'pay_time' => $order['pay_time'],
            'real_name' => $order['real_name'],
            'user_phone' => $order['user_phone'],
            'user_address' => $order['user_address'],
            'total_price' => $order['total_price'],
            'coupon_price' => $order['coupon_price'],
            'pay_price' => $order['pay_price'],
            'total_postage' => $order['total_postage'],
            'pay_postage' => $order['pay_postage'],
            'mark' => $order['mark'],
        ];
        $config = $this->checkPrinterConfig($merId);
        $printer = new Printer('yi_lian_yun', $config);
        event('order.print.before', compact('order'));

        $res = $printer->setPrinterContent([
            'name' => $order['merchant']['mer_name'],
            'orderInfo' => $data,
            'product' => $product
        ])->startPrinter();

        event('order.print', compact('order', 'res'));

        return $res;
    }

    public function batchPrinter(int $id, int $merId)
    {
        $order = $this->dao->getWhere(['order_id' => $id], '*', ['orderProduct', 'merchant' => function ($query) {
            $query->field('mer_id,mer_name');
        }]);

        foreach ($order['orderProduct'] as $item) {
            $product[] = [
                'store_name' => $item['cart_info']['product']['store_name'] . '【' . $item['cart_info']['productAttr']['sku'] . '】',
                'product_num' => $item['product_num'],
                'price' => bcdiv($item['product_price'], $item['product_num'], 2),
                'product_price' => $item['product_price'],
            ];
        }

        $data = [
            'order_sn'   => $order['order_sn'],
            'order_type' => $order['order_type'],
            'pay_time'   => $order['pay_time'],
            'real_name'  => $order['real_name'],
            'user_phone' => $order['user_phone'],
            'user_address' => $order['user_address'],
            'total_price'  => $order['total_price'],
            'coupon_price' => $order['coupon_price'],
            'pay_price'    => $order['pay_price'],
            'total_postage' => $order['total_postage'],
            'pay_postage'   => $order['pay_postage'],
            'mark' => $order['mark'],
        ];

        $printer = app()->make(StorePrinterRepository::class)->getPrinter($merId);
        event('order.print.before', compact('order'));
        foreach ($printer as $config) {
            $printer = new Printer('yi_lian_yun', $config);
            $res = $printer->setPrinterContent([
                'name' => $order['merchant']['mer_name'],
                'orderInfo' => $data,
                'product' => $product
            ])->startPrinter();
        }

        event('order.print', compact('order', 'res'));
    }


    public function verifyOrder(int $id, int $merId, array $data, $serviceId = 0)
    {
        $order = $this->dao->getWhere(['order_id' => $id, 'mer_id' => $merId,'verify_code' => $data['verify_code'],'order_type' => 1],'*',['orderProduct']);
        if (!$order) throw new ValidateException('订单不存在');
        if (!$order->paid) throw new ValidateException('订单未支付');
        if ($order['status']) throw new ValidateException('订单已全部核销，请勿重复操作');
        foreach ($data['data'] as $v) {
            $splitData[$v['id']] = $v['num'];
        }
        $spl = app()->make(StoreOrderSplitRepository::class)->splitOrder($order, $splitData, $serviceId, 1);
        if ($spl) $order = $spl;
        $order->status = 2;
        $order->verify_time = date('Y-m-d H:i:s');
        $order->verify_service_id = $serviceId;
        event('order.verify.before', compact('order'));
        //订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        Db::transaction(function () use ($order,$storeOrderStatusRepository,$serviceId) {
            $this->takeAfter($order, $order->user);
            $order->save();
            $orderStatus = [
                'order_id' => $order->order_id,
                'order_sn' => $order->order_sn,
                'type' => $storeOrderStatusRepository::TYPE_ORDER,
                'change_message' => '订单已核销',
                'change_type' => $storeOrderStatusRepository::ORDER_STATUS_TAKE,
            ];
            if ($serviceId){
                $storeOrderStatusRepository->createServiceLog($serviceId,$orderStatus);
            } else {
                $storeOrderStatusRepository->createAdminLog($orderStatus);
            }

        });
        event('order.verify', compact('order'));
    }

    public function wxQrcode($orderId, $verify_code)
    {
        $siteUrl = systemConfig('site_url');
        $name = md5('owx' . $orderId . date('Ymd')) . '.jpg';
        $attachmentRepository = app()->make(AttachmentRepository::class);
        $imageInfo = $attachmentRepository->getWhere(['attachment_name' => $name]);

        if (isset($imageInfo['attachment_src']) && strstr($imageInfo['attachment_src'], 'http') !== false && curl_file_exist($imageInfo['attachment_src']) === false) {
            $imageInfo->delete();
            $imageInfo = null;
        }
        if (!$imageInfo) {
            //            $codeUrl = set_http_type(rtrim($siteUrl, '/') . '/pages/admin/order_cancellation/index?verify_code=' . $verify_code, request()->isSsl() ? 0 : 1);//二维码链接
            $imageInfo = app()->make(QrcodeService::class)->getQRCodePath($verify_code, $name);
            if (is_string($imageInfo)) throw new ValidateException('二维码生成失败');

            $imageInfo['dir'] = tidy_url($imageInfo['dir'], null, $siteUrl);

            $attachmentRepository->create(systemConfig('upload_type') ?: 1, -2, $orderId, [
                'attachment_category_id' => 0,
                'attachment_name' => $imageInfo['name'],
                'attachment_src' => $imageInfo['dir']
            ]);
            $urlCode = $imageInfo['dir'];
        } else $urlCode = $imageInfo['attachment_src'];
        return $urlCode;
    }

    /**
     * TODO 根据商品ID获取订单数
     * @param int $productId
     * @return int
     * @author Qinii
     * @day 2020-08-05
     */
    public function seckillOrderCounut(int $productId)
    {
        $where = [
            'activity_id' => $productId,
            'product_type' => 1,
            'day' => date('Y-m-d', time())
        ];
        $count = $this->dao->getTattendCount($where, null)->count();
        $count_ = $this->dao->getSeckillRefundCount($where, 2);
        $count__ = $this->dao->getSeckillRefundCount($where, 1);
        return $count - $count_ - $count__;
    }

    /**
     * TODO 根据商品sku获取订单数
     * @param int $productId
     * @return int
     * @author Qinii
     * @day 2020-08-05
     */
    public function seckillSkuOrderCounut(string $sku)
    {
        $where = [
            'product_sku' => $sku,
            'product_type' => 1,
            'day' => date('Y-m-d', time())
        ];
        $count = $this->dao->getTattendCount($where, null)->count();
        $count_ = $this->dao->getSeckillRefundCount($where, 2);
        $count__ = $this->dao->getSeckillRefundCount($where, 1);
        return $count - $count_ - $count__;
    }

    /**
     * TODO 获取sku的总销量
     * @param string $sku
     * @return int|mixed
     * @author Qinii
     * @day 3/4/21
     */
    public function skuSalesCount(string $sku)
    {
        $where = [
            'product_sku' => $sku,
            'product_type' => 1,
        ];
        $count = $this->dao->getTattendSuccessCount($where, null)->count();
        $count_ = $this->dao->getSeckillRefundCount($where, 2);
        $count__ = $this->dao->getSeckillRefundCount($where, 1);
        return $count - $count_ - $count__;
    }

    /**
     * TODO 秒杀获取个人当天限购
     * @param int $uid
     * @param int $productId
     * @return int
     * @author Qinii
     * @day 2020-08-15
     */
    public function getDayPayCount(int $uid, int $productId)
    {
        $make = app()->make(StoreSeckillActiveRepository::class);
        $active = $make->getWhere(['product_id' => $productId]);
        if ($active['once_pay_count'] == 0) return true;

        $where = [
            'activity_id' => $productId,
            'product_type' => 1,
            'day' => date('Y-m-d', time())
        ];

        $count = $this->dao->getTattendCount($where, $uid)->count();
        return ($active['once_pay_count'] > $count);
    }

    /**
     * TODO 秒杀获取个人总限购
     * @param int $uid
     * @param int $productId
     * @return int
     * @author Qinii
     * @day 2020-08-15
     */
    public function getPayCount(int $uid, int $productId)
    {
        $make = app()->make(StoreSeckillActiveRepository::class);
        $active = $make->getWhere(['product_id' => $productId]);
        if ($active['all_pay_count'] == 0) return true;
        $where = [
            'activity_id' => $productId,
            'product_type' => 1,
            'day' => date('Y-m-d', time())
        ];
        $count = $this->dao->getTattendCount($where, $uid)->count();
        return ($active['all_pay_count'] > $count);
    }

    /**
     *  根据订单id查看是否全部退款
     * @Author:Qinii
     * @Date: 2020/9/11
     * @param int $orderId
     * @return bool
     */
    public function checkRefundStatusById(int $orderId, int $refundId)
    {
        return Db::transaction(function () use ($orderId, $refundId) {
            $res = $this->dao->search(['order_id' => $orderId])->with(['orderProduct'])->find();
            $refund = app()->make(StoreRefundOrderRepository::class)->getRefundCount($orderId, $refundId);
            if ($refund) return false;
            foreach ($res['orderProduct'] as $item) {
                if ($item['refund_num'] !== 0) return false;
                $item->is_refund = 3;
                $item->save();
            }
            $res->status = -1;
            $res->save();
            $this->orderRefundAllAfter($res);
            return true;
        });
    }

    public function orderRefundAllAfter($order)
    {

        if ($order->activity_type == 10) {
            app()->make(StoreDiscountRepository::class)->incStock($order->orderProduct[0]['activity_id']);
        }
        $mainId = $order->main_id ?: $order->order_id;
        $count = $this->query([])->where('status', '<>', -1)->where(function ($query) use ($mainId) {
            $query->where('order_id', $mainId)->whereOr('main_id', $mainId);
        })->count();
        //拆单后完全退完
        if (!$count) {
            if ($order->main_id) {
                $order = $this->query(['order_id' => $mainId])->find();
            }
            $couponId = [];
            if ($order->coupon_id) {
                $couponId = explode(',', $order->coupon_id);
            }
            app()->make(MerchantRepository::class)->computedLockMoney($order);
            //总单所有订单全部退完
            if (!$this->query([])->where('status', '<>', -1)->where('group_order_id', $order->group_order_id)->count()) {
                if ($order->groupOrder->coupon_id) {
                    $couponId[] = $order->groupOrder->coupon_id;
                }
            }
            if (count($couponId)) {
                app()->make(StoreCouponUserRepository::class)->updates($couponId, ['status' => 0]);
            }

        }
        //订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'type' => $storeOrderStatusRepository::TYPE_ORDER,
            'change_message' => '订单已全部退款',
            'change_type' => $storeOrderStatusRepository::ORDER_STATUS_REFUND_ALL,
        ];
        $storeOrderStatusRepository->createSysLog($orderStatus);

        event('order.refundAll', compact('order'));
    }

    /**
     * @param $id
     * @param $uid
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/9/17
     */
    public function userDel($id, $uid)
    {
        $order = $this->dao->getWhere([['status', 'in', [0, 3, -1, 11]], ['order_id', '=', $id], ['uid', '=', $uid], ['is_del', '=', 0]]);
        if (!$order || ($order->status == 0 && $order->paid == 1))
            throw new ValidateException('订单状态有误');
        event('order.userDel.before', compact('order'));
        $this->delOrder($order, '订单删除');
        event('order.userDel', compact('order'));
    }

    public function delOrder($order, $info = '订单删除')
    {
        //订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'type' => $storeOrderStatusRepository::TYPE_ORDER,
            'change_message' => $info,
            'change_type' => $storeOrderStatusRepository::ORDER_STATUS_DELETE,
        ];
        $productRepository = app()->make(ProductRepository::class);
        Db::transaction(function () use ($info, $order, $orderStatus, $storeOrderStatusRepository,$productRepository) {
            $order->is_del = 1;
            $order->save();
            $storeOrderStatusRepository->createUserLog($orderStatus);
            if ($order->status != 3) {
                foreach ($order->orderProduct as $cart) {
                    $productRepository->orderProductIncStock($order, $cart);
                }
            }
        });
    }

    public function merDelete($id)
    {
        Db::transaction(function () use ($id) {
            $data['is_system_del'] = 1;
            $this->dao->update($id, $data);
            app()->make(StoreOrderReceiptRepository::class)->deleteByOrderId($id);
        });
    }

    /**
     * @param $id
     * @return \FormBuilder\Form
     * @author Qinii
     */
    public function sendProductForm($id, $data)
    {
        $express = app()->make(ExpressRepository::class)->options();
        $form = Elm::createForm(Route::buildUrl('merchantStoreOrderDelivery', ['id' => $id])->build());

        if (in_array($data['delivery_type'], [1, 2])) {
            if ($data['delivery_type'] == 1) {
                $form->setRule([
                    Elm::hidden('delivery_type', 1),
                    [
                        'type' => 'span',
                        'title' => '原快递名称',
                        'children' => [(string)$data['delivery_name']]
                    ],
                    [
                        'type' => 'span',
                        'title' => '原快递单号',
                        'children' => [(string)$data['delivery_id']]
                    ],
                    Elm::select('delivery_name', '快递名称')->options(function () use ($express) {
                        return $express;
                    }),
                    Elm::input('delivery_id', '快递单号')->required(),
                ]);
            } else {
                $form->setRule([
                    Elm::hidden('delivery_type', 2),
                    [
                        'type' => 'span',
                        'title' => '原送货人姓名',
                        'children' => [(string)$data['delivery_name']]
                    ],
                    [
                        'type' => 'span',
                        'title' => '原手机号',
                        'children' => [(string)$data['delivery_id']]
                    ],
                    Elm::input('delivery_name', '送货人姓名')->required(),
                    Elm::input('delivery_id', '手机号')->required(),
                ]);
            }
        }
        if ($data['delivery_type'] == 3) {
            $form->setRule([
                Elm::hidden('delivery_type', 3),
                [
                    'type' => 'span',
                    'title' => '发货类型',
                    'children' => ['无需配送']
                ]
            ]);
        }
        if (!$data['delivery_type']) {
            $form->setRule([
                Elm::radio('delivery_type', '发货类型', 1)
                    ->setOptions([
                        ['value' => 1, 'label' => '发货'],
                        ['value' => 2, 'label' => '送货'],
                        ['value' => 3, 'label' => '无需配送'],
                    ])->control([
                        [
                            'value' => 1,
                            'rule' => [
                                Elm::select('delivery_name', '快递名称')->options(function () use ($express) {
                                    return $express;
                                }),
                                Elm::input('delivery_id', '快递单号')->required(),
                            ]
                        ],
                        [
                            'value' => 2,
                            'rule' => [
                                Elm::input('delivery_name', '送货人姓名')->required(),
                                Elm::input('delivery_id', '手机号')->required(),
                            ]
                        ],
                        [
                            'value' => 3,
                            'rule' => []
                        ],

                    ]),
            ]);
        }

        return $form->setTitle('发货信息');
    }

    /**
     * TODO 导入发货信息
     * @param array $data
     * @param $merId
     * @author Qinii
     * @day 3/16/21
     */
    public function setWhereDeliveryStatus(array $arrary, $merId)
    {
        //读取excel
        $data = SpreadsheetExcelService::instance()->_import($arrary['path'], $arrary['sql'], $arrary['where'], 4);
        if (!$data) return;
        $import_id = $arrary['import_id'];
        Db::transaction(function () use ($data, $merId, $import_id) {
            $result = [];
            $num = 0;
            $count = 0;
            $status = 0;
            foreach ($data as $datum) {
                $value = [];
                $ret = [];
                if ($datum['where']) {
                    $count = $count + 1;
                    if (empty($datum['value']['delivery_id'])) {
                        $mark = '发货单号为空';
                    } else {
                        $ret = $this->getSearch([])
                            ->where('status', 0)
                            ->where('paid', 1)
                            ->where('order_type', 0)
                            ->where('mer_id', $merId)
                            ->where($datum['where'])
                            ->find();
                        $mark = '数据有误或已发货';
                    }
                    if ($ret) {
                        try {
                            $value = array_merge($datum['value'], ['status' => 1]);
                            $value['delivery_type'] = 1;
                            $this->delivery($ret['order_id'], $merId, $value);

                            $status = 1;
                            $mark = '';

                            $num = $num + 1;
                        } catch (\Exception $exception) {
                            $mark = $exception->getMessage();
                        }
                    }
                    $datum['where']['mark'] = $mark;
                    $datum['where']['mer_id'] = $merId;
                    $datum['where']['status'] = $status;
                    $datum['where']['import_id'] = $import_id;
                    $result[] = array_merge($datum['where'], $datum['value']);
                }
            }
            // 记录入库操作
            if (!empty($result)) app()->make(StoreImportDeliveryRepository::class)->insertAll($result);
            $_status = ($count == $num) ? 1 : (($num < 1) ? -1 : 10);
            app()->make(StoreImportRepository::class)->update($import_id, ['count' => $count, 'success' => $num, 'status' => $_status]);
        });
        if (file_exists($arrary['path'])) unlink($arrary['path']);
    }

    /**
     * TODO 根据订单查询相关联的自订单
     * @param $id
     * @param $merId
     * @return \think\Collection
     * @author Qinii
     * @day 2023/2/22
     */
    public function childrenList($id,$merId)
    {
        $data = $this->dao->get($id);
        $query = $this->dao->getSearch([])->with(['orderProduct'])->where('order_id','<>',$id);
        if ($merId) $query->where('mer_id',$merId);
        if ($data['main_id']) {
            $query->where(function($query) use($data,$id){
                $query->where('main_id',$data['main_id'])->whereOr('order_id',$data['main_id']);
            });
        } else {
            $query->where('main_id',$id);
        }
        return $query->select();
    }

    public function pointsOrderList(array $where, int $page, int $limit)
    {
        $query = $this->dao->searchAll($where,0,1)->where('is_del', 0);
        $count = $query->count();
        $list = $query->with(['orderProduct'])->page($page, $limit)->select();
        return compact('count','list');
    }

    public function pointsOrderAdminList(array $where, int $page, int $limit)
    {
        $query = $this->dao->searchAll($where,null,1);
        $count = $query->count();
        $list = $query->with(['orderProduct'])->page($page, $limit)->select();
        return compact('count','list');
    }

    public function pointsDetail($id, $uid)
    {
        $where = [];
        $with = ['orderProduct',];
        if ($uid) {
            $where['uid'] = $uid;
        } else if (!$uid) {
            $with['user'] = function ($query) {
                return $query->field('uid,nickname');
            };
        }
        $order = $this->dao->searchAll($where,0,1)->where('order_id', $id)->where('StoreOrder.is_del', 0)->with($with)->append(['cancel_time'])->find();
        if (!$order) return null;
        return $order;
    }
}
