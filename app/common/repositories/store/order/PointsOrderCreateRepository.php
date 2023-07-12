<?php

namespace app\common\repositories\store\order;

use app\common\dao\store\order\StoreOrderDao;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\product\ProductAssistSkuRepository;
use app\common\repositories\store\product\ProductAttrValueRepository;
use app\common\repositories\store\product\ProductGroupSkuRepository;
use app\common\repositories\store\product\ProductPresellSkuRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserAddressRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserMerchantRepository;
use think\exception\ValidateException;
use think\facade\Db;

class PointsOrderCreateRepository
{
    public function check($user, $cartId, $addressId,  $useIntegral , $createOrder = null)
    {
        $key = md5(json_encode(compact('cartId', 'useIntegral', 'addressId'))) .  $user->uid;
        $address = $this->validateAddress($user,$addressId);
        [$merchantCartList,$order_model,$order_extend,$order_type] = $this->validateCartList($cartId,$user,$address);
        $successData = $this->validateMerchantList($merchantCartList);
        return $successData + [
                'key' => $key,
                'order_type' => $order_type,
                'order_model' => $order_model,
                'order_extend' => $order_extend,
                'true_integral' => (int)$user->integral,
                'address' => $address,
                ];
    }

    /**
     * TODO 验证用户地址
     * @author Qinii
     * @day 2023/4/20
     */
    public function validateAddress($user,$addressId)
    {
        $address = null;
        //验证地址
        if ($addressId) {
            $addressRepository = app()->make(UserAddressRepository::class);
            $address = $addressRepository->getWhere(['uid' => $user->uid, 'address_id' => $addressId]);
        }
        return $address;
    }

    /**
     * TODO 验证购物车商品
     * @author Qinii
     * @day 2023/4/20
     */
    public function validateCartList($cartId,$user,$address)
    {
        $storeCartRepository = app()->make(StoreCartRepository::class);
        $list = $storeCartRepository->cartIbByData($cartId, $user->uid, $address);
        $res = $storeCartRepository->checkCartList($list, 0, $user);
        $merchantCartList = $res['list'];
        $fail = $res['fail'];
        //检查购物车失效数据
        if (count($fail)) {
            if ($fail[0]['is_fail'])
                throw new ValidateException('[已失效]' . mb_substr($fail[0]['product']['store_name'],0,10).'...');
            if (in_array($fail[0]['product_type'], [1, 2, 3]) && !$fail[0]['userPayCount']) {
                throw new ValidateException('[超出限购数]' . mb_substr($fail[0]['product']['store_name'],0,10).'...');
            }
            throw new ValidateException('[已失效]' . mb_substr($fail[0]['product']['store_name'],0,10).'...');
        }
        $order_type = null;
        $order_model = 2;
        $order_extend = '';
        //检查商品类型, 活动商品只能单独购买
        foreach ($merchantCartList as $merchantCart) {
            foreach ($merchantCart['list'] as $cart) {
                if (is_null($order_type)) $order_type = $cart['product_type'];
                if (count($merchantCart['list']) != 1 || count($merchantCartList) != 1) {
                    throw new ValidateException('活动商品必须单独购买');
                }
                if ($cart['product']['pay_limit'] == 2){
                    //如果长期限购
                    //已购买数量
                    $count = app()->make(StoreOrderRepository::class)->getMaxCountNumber($cart['uid'],$cart['product_id']);
                    if (($cart['cart_num'] + $count) > $cart['product']['once_max_count'])
                        throw new ValidateException('[超出限购总数：'. $cart['product']['once_max_count'].']'.mb_substr($cart['product']['store_name'],0,10).'...');
                }
                if ($cart['product']['extend']) {
                    $order_extend = json_decode($cart['product']['extend'], true);
                }
            }
        }
        return [$merchantCartList,$order_model,$order_extend,$order_type];
    }

    /**
     * TODO 计算订单金额
     * @return array
     * @author Qinii
     * @day 2023/4/20
     */
    public function validateMerchantList($merchantCartList)
    {
        $deliveryStatus = true;
        $order_total_num = 0;
        $order_total_price = 0;
        $order_total_integral = 0;
        $order_total_cost = 0;
        $order_pay_price = 0;
        foreach ($merchantCartList as &$merchantCart) {
            //每个商户的订单金额等
            //合计支付金额
            $total_price = 0;
            //合计商品数量
            $total_num = 0;
            //合计运费
            $postage_price = 0;
            //合计使用积分
            $total_integral = 0;
            //合计出厂价
            $total_cost = 0;
            //合计实际支付金额
            $pay_price = 0;
            //每个商户的商品列表，计算
            foreach ($merchantCart['list'] as $cart) {
                if ($cart['cart_num'] <= 0) throw new ValidateException('购买商品数必须大于0');
                $price = bcmul($cart['cart_num'], $cart['productAttr']['price'], 2);
                $integral = bcmul($cart['cart_num'], $cart['productAttr']['ot_price'], 2);
                $cost = bcmul($cart['cart_num'], $cart['productAttr']['cost'], 2);
                $total_num += $cart['cart_num'];
                $total_price = bcadd($total_price, $price, 2);
                $total_integral = bcadd($total_integral, $integral, 2);
                $total_cost = bcadd($total_cost, $cost, 2);
                $pay_price = bcadd($pay_price, $price, 2);

                $cart['true_price'] = $price;
                $cart['total_price'] = $price;
                $cart['postage_price'] = 0;
                $cart['integral'] = (int)$integral;
                $cart['extension_one'] = 0;
                $cart['extension_two'] = 0;
                $cart['cost'] = $total_cost;
            }
            unset($cart);
            if (count($merchantCartList) > 1 || count($merchantCart['list']) > 1) {
                $orderDeliveryStatus = $orderDeliveryStatus && $deliveryStatus;
            }
            $order_total_num += $total_num;
            $order_total_price = bcadd($order_total_price,$total_price, 2);
            $order_total_cost = bcadd($order_total_cost,$total_cost,2);
            $order_pay_price = bcadd($order_pay_price,$pay_price,  2);
            $order_total_integral = bcadd($order_total_integral,$total_integral,2);
            $merchantCart['order'] = [
                'total_num'  => $total_num,
                'total_price'=> $total_price,
                'total_cost' => $total_cost,
                'pay_price'  => $pay_price,
                'total_integral'=> $total_integral,
                'postage_price' => $postage_price,
            ];
        }
        unset($merchantCart);
        return [
            'order_total_num' => $order_total_num,
            'order_pay_price' => $order_pay_price,
            'order_total_price' => $order_total_price,
            'order_total_integral' => (int)$order_total_integral,
            'order_total_cost' => $order_total_cost,
            'order_delivery_status' => $deliveryStatus,
            'order' => $merchantCartList
        ];
    }

    /**
     * TODO 循环处理子订单
     * @param $merchantCartList
     * @param $pay_type
     * @param $user_address
     * @return array
     * @author Qinii
     * @day 2023/4/21
     */
    public function merOrderList($user, $merchantCartList, $pay_type, $user_address, $address, $order_type, $order_model, $mark = '', $extend = [])
    {
        $totalPostage = 0;
        $totalCost = 0;
        $totalNum = 0;
        $totalPrice = 0;
        $totalIntegral = 0;
        $make = app()->make(StoreOrderRepository::class);
        foreach ($merchantCartList as $k => $merchantCart) {
            //整理订单数据
            $_order = [
                'cartInfo' => $merchantCart,
                'activity_type' => $order_type,
                'commission_rate' => 0,
                'order_type' => $order_type,
                'is_virtual' => $order_model,
                'extension_one' => $total_extension_one ?? 0,
                'extension_two' => $total_extension_two ?? 0,
                'order_sn' => $make->getNewOrderId(StoreOrderRepository::TYPE_SN_ORDER) . ($k + 1),
                'uid' => $user->uid,
                'spread_uid' => $spreadUid ?? 0,
                'top_uid' => $topUid ?? 0,
                'is_selfbuy' => $isSelfBuy ?? 0,
                'real_name' => $address['real_name'] ?? '',
                'user_phone' => $address['phone'] ?? '',
                'user_address' => $user_address,
                'cart_id' => implode(',', array_column($merchantCart['list'], 'cart_id')),
                'total_num' => $merchantCart['order']['total_num'],
                'total_price' => $merchantCart['order']['total_price'],
                'total_postage' => 0,
                'pay_postage' => 0,
                'svip_discount' => 0,
                'pay_price' => $merchantCart['order']['pay_price'],
                'integral' => $merchantCart['order']['total_integral'],
                'integral_price' => 0,
                'give_integral' => 0,
                'mer_id' => $merchantCart['mer_id'],
                'cost' => $merchantCart['order']['total_cost'],
                'order_extend' => count($extend) ? json_encode($extend, JSON_UNESCAPED_UNICODE) : '',
                'coupon_id' => '',
                'mark' => $mark,
                'coupon_price' => 0,
                'platform_coupon_price' => 0,
                'pay_type' => $pay_type
            ];
            $orderList[] = $_order;
            $totalCost = bcadd($totalCost, $_order['cost'], 2);
            $totalPrice = bcadd($totalPrice, $_order['total_price'], 2);
            $totalIntegral = bcadd($totalIntegral, $_order['integral'], 2);
            $totalPostage = bcadd($totalPostage, $_order['total_postage'], 2);
            $totalNum += $merchantCart['order']['total_num'];
        }
        return compact('totalPrice','totalPostage','totalCost','totalNum','totalIntegral','orderList');
    }

    /**
     * TODO 创建订单
     * @param $params
     * @author Qinii
     * @day 2023/4/20
     */
    public function createOrder($user,$cartId,$addressId,$useIntegral,$mark,$pay_type)
    {
        $orderInfo = $this->check($user,$cartId,$addressId,$useIntegral, true);
        if (!$orderInfo['address']) throw new ValidateException('请选择收货地址');
        if (!$orderInfo['address']['province_id']) throw new ValidateException('请完善收货地址信息');
        if (!$orderInfo['order_delivery_status']) throw new ValidateException('部分商品配送方式不一致,请单独下单');
        if ($orderInfo['order_total_price'] > 1000000) throw new ValidateException('支付金额超出最大限制');
        if ($orderInfo['order_total_integral'] > $user->integral) throw new ValidateException('积分不足');
        $merchantCartList = $orderInfo['order'];
        $address =$orderInfo['address'];
        $user_address = isset($address) ? ($address['province'] . $address['city'] . $address['district'] . $address['street'] . $address['detail']) : '';

        $orderList = $this->merOrderList($user,$merchantCartList, $pay_type, $user_address, $address,$orderInfo['order_type'],$orderInfo['order_model'],$mark);
        $storeOrderRepository = app()->make(StoreOrderRepository::class);
        $groupOrder = [
            'uid' => $user->uid,
            'group_order_sn' => count($orderList) === 1 ? $orderList[0]['order_sn'] : ($storeOrderRepository->getNewOrderId(StoreOrderRepository::TYPE_SN_ORDER) . '20'),
            'total_postage' => $orderList['totalPostage'],
            'total_price' => $orderList['totalPrice'],
            'total_num' => $orderList['totalNum'],
            'real_name' => $address['real_name'] ?? '',
            'user_phone' => $address['phone'] ?? '',
            'user_address' => $user_address,
            'pay_price' => $orderList['totalPrice'],
            'coupon_price' => 0,
            'pay_postage' => $orderList['totalPostage'],
            'cost' => $orderList['totalCost'],
            'coupon_id' => '',
            'pay_type' => $pay_type,
            'give_coupon_ids' => 0,
            'integral' => $orderList['totalIntegral'],
            'integral_price' => $orderList['totalIntegral'],
            'give_integral' => 0,
            'is_combine' => 0,
            'activity_type' => $orderInfo['order_type'],
        ];

        //订单记录
        $storeGroupOrderRepository = app()->make(StoreGroupOrderRepository::class);
        $storeCartRepository = app()->make(StoreCartRepository::class);
        $attrValueRepository = app()->make(ProductAttrValueRepository::class);
        $productRepository = app()->make(ProductRepository::class);
        $storeOrderProductRepository = app()->make(StoreOrderProductRepository::class);
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $userMerchantRepository = app()->make(UserMerchantRepository::class);
        $userBillRepository = app()->make(UserBillRepository::class);
        $_orderList = $orderList['orderList'];
        return Db::transaction(function () use (
            $user,
            $cartId,
            $groupOrder,
            $_orderList,
            $orderInfo,
            $attrValueRepository,
            $productRepository,
            $storeCartRepository,
            $storeGroupOrderRepository,
            $storeOrderStatusRepository,
            $userMerchantRepository,
            $storeOrderProductRepository,
            $storeOrderRepository,
            $userBillRepository
        ) {
            //减库存
            foreach ($_orderList as $order) {
                foreach ($order['cartInfo']['list'] as $cart) {
                    if (!isset($uniqueList[$cart['productAttr']['product_id'] . $cart['productAttr']['unique']]))
                        $uniqueList[$cart['productAttr']['product_id'] . $cart['productAttr']['unique']] = true;
                    else
                        throw new ValidateException('购物车商品信息重复');
                    try {
                        $attrValueRepository->descStock($cart['productAttr']['product_id'], $cart['productAttr']['unique'], $cart['cart_num']);
                        $productRepository->descStock($cart['product']['product_id'], $cart['cart_num']);
                        $productRepository->incIntegral($cart['product']['product_id'], $cart['integral'], $cart['integral']);
                    } catch (\Exception $e) {
                        throw new ValidateException('库存不足');
                    }
                }
            }
            //修改购物车状态
            $storeCartRepository->updates($cartId, ['is_pay' => 1]);
            //创建订单
            $groupOrder = $storeGroupOrderRepository->create($groupOrder);
            if ($groupOrder['integral'] > 0) {
                $user->integral = bcsub($user->integral, $groupOrder['integral'], 0);
                $userBillRepository->decBill(
                    $user['uid'],
                    'integral',
                    'points_order',
                    [
                        'link_id' => $groupOrder['group_order_id'],
                        'status' => 1,
                        'title' => '积分商城兑换商品',
                        'number' => $groupOrder['integral'],
                        'mark' => '积分商城兑换商品使用积分' . floatval($groupOrder['integral']) ,
                        'balance' => $user->integral
                    ]
                );
                $user->save();
            }
            foreach ($_orderList as $k => $order) {
                $_orderList[$k]['group_order_id'] = $groupOrder->group_order_id;
            }

            $orderProduct = [];
            $orderStatus = [];
            foreach ($_orderList as $order) {
                $cartInfo = $order['cartInfo'];
                unset($order['cartInfo']);
                //创建子订单
                $_order = $storeOrderRepository->create($order);
                foreach ($cartInfo['list'] as $cart) {
                    $productPrice = $cart['true_price'];
                    $order_cart = [
                        'product' => $cart['product'],
                        'productAttr' => $cart['productAttr'],
                        'product_type' => $cart['product_type']
                    ];
                    $orderProduct[] = [
                        'order_id' => $_order->order_id,
                        'cart_id' => $cart['cart_id'],
                        'uid' => $user->uid,
                        'product_id' => $cart['product_id'],
                        'activity_id' => $cart['source'] >= 2 ? $cart['source_id'] : $cart['product_id'],
                        'total_price' => $cart['total_price'],
                        'product_price' => $productPrice,
                        'extension_one' => 0,
                        'extension_two' => 0,
                        'postage_price' => 0,
                        'svip_discount' => 0,
                        'cost' => $cart['cost'],
                        'coupon_price' => 0,
                        'platform_coupon_price' => 0,
                        'product_sku' => $cart['productAttr']['unique'],
                        'product_num' => $cart['cart_num'],
                        'refund_num' => $cart['cart_num'],
                        'integral_price' =>  0,
                        'integral' => $cart['integral'] ,
                        'integral_total' => $cart['integral'] ,
                        'product_type' => $cart['product_type'],
                        'cart_info' => json_encode($order_cart)
                    ];
                }
                $userMerchantRepository->getInfo($user->uid, $order['mer_id']);
                //订单记录
                $orderStatus[] = [
                    'order_id' => $_order->order_id,
                    'order_sn' => $_order->order_sn,
                    'type' => $storeOrderStatusRepository::TYPE_ORDER,
                    'change_message' => '积分兑换订单生成',
                    'change_type' => $storeOrderStatusRepository::ORDER_STATUS_CREATE,
                    'uid' => $user->uid,
                    'nickname' => $user->nickname,
                    'user_type' => $storeOrderStatusRepository::U_TYPE_USER,
                ];
            }

            $storeOrderStatusRepository->batchCreateLog($orderStatus);
            $storeOrderProductRepository->insertAll($orderProduct);
            event('order.create', compact('groupOrder'));
            return $groupOrder;
        });
    }
}
