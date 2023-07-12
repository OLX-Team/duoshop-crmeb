<?php

namespace app\common\repositories\store\order;

use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\product\ProductAssistSkuRepository;
use app\common\repositories\store\product\ProductAttrValueRepository;
use app\common\repositories\store\product\ProductGroupSkuRepository;
use app\common\repositories\store\product\ProductPresellSkuRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\StoreDiscountRepository;
use app\common\repositories\store\StoreCategoryRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\MemberinterestsRepository;
use app\common\repositories\user\UserAddressRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserMerchantRepository;
use app\common\repositories\user\UserRepository;
use app\validate\api\OrderVirtualFieldValidate;
use app\validate\api\UserAddressValidate;
use crmeb\jobs\SendSmsJob;
use crmeb\services\SwooleTaskService;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Queue;

class StoreOrderCreateRepository extends StoreOrderRepository
{

    public function v2CartIdByOrderInfo($user, array $cartId, array $takes = null, array $useCoupon = null, bool $useIntegral = false, int $addressId = null, $createOrder = false)
    {
        $uid = $user->uid;
        $userIntegral = $user->integral;
        $key = md5(json_encode(compact('cartId', 'takes', 'useCoupon', 'useIntegral', 'addressId'))) . $uid;
        app()->make(StoreCouponUserRepository::class)->failCoupon();
        $address = null;
        //验证地址
        if ($addressId) {
            $addressRepository = app()->make(UserAddressRepository::class);
            $address = $addressRepository->getWhere(['uid' => $uid, 'address_id' => $addressId]);
        }

        $storeCartRepository = app()->make(StoreCartRepository::class);
        $res = $storeCartRepository->checkCartList($storeCartRepository->cartIbByData($cartId, $uid, $address), 0, $user);
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

        $svip_status = $user->is_svip > 0 && systemConfig('svip_switch_status') == '1';
        $svip_integral_rate = $svip_status ? app()->make(MemberinterestsRepository::class)->getSvipInterestVal(MemberinterestsRepository::HAS_TYPE_PAY) : 0;
        //订单活动类型
        $order_type = 0;
        //虚拟订单
        $order_model = 0;
        //虚拟订单自定义数据
        $order_extend = [];
        //检查商品类型, 活动商品只能单独购买
        foreach ($merchantCartList as $merchantCart) {
            foreach ($merchantCart['list'] as $cart) {
                if ($cart['product_type']==0) {
                    if ($cart['product']['once_min_count'] > 0 &&  $cart['product']['once_min_count'] > $cart['cart_num'])
                        throw new ValidateException('[低于起购数:'.$cart['product']['once_min_count'].']'.mb_substr($cart['product']['store_name'],0,10).'...');
                    if ($cart['product']['pay_limit'] == 1 && $cart['product']['once_max_count'] < $cart['cart_num'])
                        throw new ValidateException('[超出单次限购数：'.$cart['product']['once_max_count'].']'.mb_substr($cart['product']['store_name'],0,10).'...');
                    if ($cart['product']['pay_limit'] == 2){
                        //如果长期限购
                        //已购买数量
                        $count = app()->make(StoreOrderRepository::class)->getMaxCountNumber($cart['uid'],$cart['product_id']);
                        if (($cart['cart_num'] + $count) > $cart['product']['once_max_count'])
                            throw new ValidateException('[超出限购总数：'. $cart['product']['once_max_count'].']'.mb_substr($cart['product']['store_name'],0,10).'...');
                    }
                }

                if ($cart['product_type'] > 0) $order_type = $cart['product_type'];
                if ($cart['product_type'] > 0 && (($cart['product_type'] != 10 && count($merchantCart['list']) != 1) || count($merchantCartList) != 1)) {
                    throw new ValidateException('活动商品必须单独购买');
                }
                if ($cart['product']['type'] && (count($merchantCart['list']) != 1 || count($merchantCartList) != 1)) {
                    throw new ValidateException('虚拟商品必须单独购买');
                }
                $order_model = $cart['product']['type'];
                if ($cart['product']['extend']) {
                    $order_extend = json_decode($cart['product']['extend'], true);
                }
            }
        }
        unset($merchantCart, $cart);

        $order_price = 0;
        $total_true_price = 0;
        $order_total_price = 0;
        $order_coupon_price = 0;
        $noDeliver = false;
        $presellType = 0;
        $fn = [];
        $enabledPlatformCoupon = !$order_type;
        $order_total_postage = 0;
        //套餐订单
        if ($order_type == 10) {
            app()->make(StoreDiscountRepository::class)
                ->check($merchantCartList[0]['list'][0]['source_id'], $merchantCartList[0]['list'], $user);
        }

        $orderDeliveryStatus = true;
        $order_svip_discount = 0;
        // 循环计算每个店铺的订单数据
        foreach ($merchantCartList as &$merchantCart) {
            $postageRule = [];
            $total_price = 0;
            $total_num = 0;
            $valid_total_price = 0;
            $postage_price = 0;
            $product_price = [];
            $final_price = 0;
            $down_price = 0;
            $total_svip_discount = 0;
            $order_refund_switch = 0;
            //是否自提
            $isTake = in_array($merchantCart['mer_id'], $takes ?? []);

//            if (!$createOrder && !$isTake) {
//                $isTake = count($merchantCart['delivery_way']) == 1 && $merchantCart['delivery_way'][0] == '1';
//            }

            $merTake = in_array('1', $merchantCart['delivery_way'], true);
            $merDelivery = (!$merchantCart['delivery_way'] || !count($merchantCart['delivery_way']) || in_array('2', $merchantCart['delivery_way'], true));
            $_merTake = $merTake;
            $_merDelivery = $merDelivery;
            $deliveryStatus = true;
            if ($createOrder && $isTake && !$merTake) {
                $deliveryStatus = false;
//                throw new ValidateException('[仅支持快递配送]' . $merchantCart['mer_name']);
            }
            $product_cart = [];

            foreach ($merchantCart['list'] as $k => $cart) {
                //获取订单类型, 活动商品单次只能购买一个
                if ($cart['product']['delivery_way']) {
                    $delivery_way = explode(',', $cart['product']['delivery_way']);
                    $proTake = in_array('1', $delivery_way, true);
                    $merTake = $merTake && $proTake;
                    $proDelivery = (!count($delivery_way) || in_array('2', $delivery_way, true));
                    $merDelivery = $merDelivery && $proDelivery;
                    $merchantCart['list'][$k]['allow_take'] = $proTake;
                    $merchantCart['list'][$k]['allow_delivery'] = $proDelivery;
                } else {
                    $merchantCart['list'][$k]['allow_take'] = $_merTake;
                    $merchantCart['list'][$k]['allow_delivery'] = $_merDelivery;
                }
                if ($createOrder && $isTake && !$merTake) {
                    $deliveryStatus = false;
//                    throw new ValidateException('[仅支持快递配送]' . $cart['product']['store_name']);
                }
            }
            if (!$merDelivery && !$merTake) {
                $deliveryStatus = false;
//                throw new ValidateException('部分商品配送方式不一致,请单独下单');
            }
            if ($deliveryStatus && !$isTake && ($merDelivery || $merTake)) {
                $isTake = $merDelivery ? 0 : 1;
            }
            //加载商品数据
            foreach ($merchantCart['list'] as $cart) {
                //预售订单
                if ($cart['product_type'] == 2) {
                    $cart->append(['productPresell', 'productPresellAttr']);
                    //助力订单
                } else if ($cart['product_type'] == 3) {
                    $cart->append(['productAssistAttr']);
                    //拼团订单
                } else if ($cart['product_type'] == 4) {
                    $cart->append(['activeSku']);
                    //套餐订单
                } else if ($cart['product_type'] == 10) {
                    $cart->append(['productDiscount', 'productDiscountAttr']);
                }

                //如果是预售订单 获取预售的订单的首款,尾款预售类型
                if ($order_type == 2) {
                    $final_price = bcadd($final_price, bcmul($cart['cart_num'], $cart['productPresellAttr']['final_price'], 2), 2);
                    $presellType = $cart['productPresell']['presell_type'];
                    if ($presellType == 2)
                        $down_price = bcadd($down_price, bcmul($cart['cart_num'], $cart['productPresellAttr']['down_price'], 2), 2);
                }
            }
            unset($cart);

            $enabledCoupon = !($order_type && $order_type != 2);

            //只有预售和普通商品可以用优惠券
            if (!$enabledCoupon) {
                $merchantCart['coupon'] = [];
            }
            $svip_coupon_merge = merchantConfig($merchantCart['mer_id'], 'svip_coupon_merge');
            $use_svip = 0;
            //获取运费规则和统计商品数据
            foreach ($merchantCart['list'] as &$cart) {

                if ($cart['product_type'] == 10 && $cart['productDiscountAttr']) {
                    $cart['productAttr']['price'] = $cart['productDiscountAttr']['active_price'];
                    $cart['productAttr']['show_svip_price'] = false;
                }

                if ($cart['cart_num'] <= 0) {
                    throw new ValidateException('购买商品数必须大于0');
                }
                $svip_discount = 0;

                $price = bcmul($cart['cart_num'], $this->cartByPrice($cart), 2);
                $cart['total_price'] = $price;
                $cart['postage_price'] = 0;
                $cart['svip_discount'] = 0;
                $total_price = bcadd($total_price, $price, 2);
                $total_num += $cart['cart_num'];
                $_price = bcmul($cart['cart_num'], $this->cartByCouponPrice($cart), 2);
                $cart['svip_coupon_merge'] = 1;
                if ($cart['productAttr']['show_svip_price'] && !$cart['product_type']) {
                    $svip_discount = max(bcmul($cart['cart_num'], bcsub($cart['productAttr']['org_price'] ?? 0, $cart['productAttr']['price'], 2), 2), 0);
                    if ($svip_coupon_merge != '1') {
                        $_price = 0;
                        $cart['svip_coupon_merge'] = 0;
                    }
                    $use_svip = 1;
                }
                $valid_total_price = bcadd($valid_total_price, $_price, 2);
                $cart['allow_price'] = $_price;
                $temp1 = $cart['product']['temp'];
                $cart['temp_number'] = 0;
                $total_svip_discount = bcadd($total_svip_discount, $svip_discount, 2);
                $cart['svip_discount'] = $svip_discount;
                $cart['refund_switch'] = $cart['product']['refund_switch'];
                if ($cart['refund_switch']) $order_refund_switch = 1;

                if (!isset($product_cart[$cart['product_id']]))
                    $product_cart[$cart['product_id']] = [$cart['cart_id']];
                else
                    $product_cart[$cart['product_id']][] = $cart['cart_id'];

                if ($_price > 0) {
                    $product_price[$cart['product_id']] = bcadd($product_price[$cart['product_id']] ?? 0, $_price, 2);
                }

                if (!$temp1) continue;

                $number = $this->productByTempNumber($cart);
                if ($number <= 0) continue;
                $cart['temp_number'] = $number;

                if ($order_model || !$temp1 || ($cart['product_type'] == 10 && $cart['productDiscount']['free_shipping'])) {
                    continue;
                }

                $free = $temp1['free'][0] ?? null;
                $region = $temp1['region'][0] ?? null;

                if (!$cart['product']['delivery_free'] && !$isTake && (!$address || !$cart['product']['temp'] || ($temp1['undelivery'] == 2 && !$free && (!$region || !$region['city_id'])))) {
                    $cart['undelivered'] = true;
                    $noDeliver = true;
                    continue;
                }
                $cart['undelivered'] = (!$isTake) && $temp1['undelivery'] == 1 && isset($temp1['undelives']);
                $fn[] = function () use ($cart) {
                    unset($cart['product']['temp']);
                };

                if ($cart['undelivered']) {
                    $noDeliver = true;
                    continue;
                }
                if ($cart['product']['delivery_free']) {
                    continue;
                }
                $tempId = $cart['product']['temp_id'];
                if (!isset($postageRule[$tempId])) {
                    $postageRule[$tempId] = [
                        'free' => null,
                        'region' => null,
                        'cart' => [],
                        'price' => 0,
                    ];
                }

                $freeRule = $postageRule[$tempId]['free'];
                $regionRule = $postageRule[$tempId]['region'];
                $postageRule[$tempId]['cart'][] = $cart['cart_id'];
                $postageRule[$tempId]['price'] = bcadd($postageRule[$tempId]['price'], $cart['price'], 2);

                if ($temp1['appoint'] && $free) {
                    if (!isset($freeRule)) {
                        $freeRule = $free;
                        $freeRule['cart_price'] = 0;
                        $freeRule['cart_number'] = 0;
                    }
                    $freeRule['cart_number'] = bcadd($freeRule['cart_number'], $number, 2);
                    $freeRule['cart_price'] = bcadd($freeRule['cart_price'], $price, 2);
                }

                if ($region) {
                    if (!isset($regionRule)) {
                        $regionRule = $region;
                        $regionRule['cart_price'] = 0;
                        $regionRule['cart_number'] = 0;
                    }
                    $regionRule['cart_number'] = bcadd($regionRule['cart_number'], $number, 2);
                    $regionRule['cart_price'] = bcadd($regionRule['cart_price'], $price, 2);
                }
                $postageRule[$tempId]['free'] = $freeRule;
                $postageRule[$tempId]['region'] = $regionRule;
            }
            unset($cart);

            if (!$isTake) {
                //计算运费
                foreach ($postageRule as $item) {
                    $freeRule = $item['free'];
                    if ($freeRule && $freeRule['cart_number'] >= $freeRule['number'] && $freeRule['cart_price'] >= $freeRule['price'])
                        continue;
                    if (!$item['region']) continue;
                    $regionRule = $item['region'];
                    $postage = $regionRule['first_price'];
                    if ($regionRule['first'] > 0 && $regionRule['cart_number'] > $regionRule['first']) {
                        $num = ceil(bcdiv(bcsub($regionRule['cart_number'], $regionRule['first'], 2), $regionRule['continue'], 2));
                        $postage = bcadd($postage, bcmul($num, $regionRule['continue_price'], 2), 2);
                    }
                    $postage_price = bcadd($postage_price, $postage, 2);
                    $cartNum = count($item['cart']);
                    //计算每个商品的运费比例
                    foreach ($merchantCart['list'] as &$cart) {
                        if (in_array($cart['cart_id'], $item['cart'])) {
                            if (--$cartNum) {
                                $cart['postage_price'] = bcmul($postage, bcdiv($cart['temp_number'], $regionRule['cart_number'], 3), 2);
                                $postage = bcsub($postage, $cart['postage_price'], 2);
                            } else {
                                $cart['postage_price'] = $postage;
                            }
                        }
                    }
                    unset($cart);
                }
                unset($item);
            }

            $coupon_price = 0;
            $use_coupon_product = [];
            $use_store_coupon = 0;

            $useCouponFlag = isset($useCoupon[$merchantCart['mer_id']]);
            $merCouponIds = (array)($useCoupon[$merchantCart['mer_id']] ?? []);
            $merCouponIds = array_reverse($merCouponIds);
            $sortIds = $merCouponIds;
//            $all_coupon_product = [];
            unset($defaultSort);
            $defaultSort = [];
            if (count($merCouponIds)) {
                foreach ($merchantCart['coupon'] as &$item) {
                    $defaultSort[] = &$item;
                    if (!in_array($item['coupon_user_id'], $sortIds, true)) {
                        $sortIds[] = $item['coupon_user_id'];
                    }
                }
                unset($item);
                usort($merchantCart['coupon'], function ($a, $b) use ($sortIds) {
                    return array_search($a['coupon_user_id'], $sortIds) > array_search($b['coupon_user_id'], $sortIds) ? 1 : -1;
                });
            }

            //过滤不可用店铺优惠券
            foreach ($merchantCart['coupon'] as &$coupon) {
                if (!$coupon['coupon']['type']) continue;

                $coupon['disabled'] = false;
                $coupon['checked'] = false;

                if (count(array_intersect(array_column($coupon['product'], 'product_id'), array_keys($product_price))) == 0) {
                    $coupon['disabled'] = true;
                    continue;
                }
                if($svip_coupon_merge != '1' && $use_svip){
                    $coupon['disabled'] = true;
                    continue;
                }
                $flag = false;
                foreach ($coupon['product'] as $_product) {
                    if (isset($product_price[$_product['product_id']]) && $product_price[$_product['product_id']] >= $coupon['use_min_price']) {
                        $flag = true;
                        break;
                    }
                }
                if (!$flag) {
                    $coupon['disabled'] = true;
                }
//                if (!$coupon['disabled']) {
//                    $all_coupon_product[] = $coupon['coupon_user_id'];
//                }
            }

            unset($coupon);

            //if ($useCouponFlag && count(array_diff($all_coupon_product, $use_coupon_product))) {
            //    throw new ValidateException('请选择有效的商品券');
            //}
            //计算商品券金额
            foreach ($merchantCart['coupon'] as &$coupon) {
                if (!$coupon['coupon']['type']) continue;
                if ($coupon['disabled']) continue;

                foreach ($coupon['product'] as $_product) {
                    if (isset($product_price[$_product['product_id']]) && $product_price[$_product['product_id']] >= $coupon['use_min_price']) {
                        if ($useCouponFlag) {
                            if (!in_array($coupon['coupon_user_id'], $merCouponIds) || isset($use_coupon_product[$_product['product_id']])) {
                                continue;
                            }
                        } else if (isset($use_coupon_product[$_product['product_id']])) {
                            continue;
                        }
                        $coupon_price = bcadd($coupon_price, $coupon['coupon_price'], 2);
                        $use_coupon_product[$_product['product_id']] = $coupon;
                        $coupon['checked'] = true;
                        break;
                    }
                }
                unset($_product);
            }
            unset($coupon);
            $pay_price = max(bcsub($valid_total_price, $coupon_price, 2), 0);
            $_pay_price = $pay_price;
            //计算店铺券
            foreach ($merchantCart['coupon'] as &$coupon) {
                if ($coupon['coupon']['type']) continue;
                $coupon['checked'] = false;
                $coupon['disabled'] = $pay_price <= 0;
                if ($use_store_coupon || $pay_price <= 0) continue;
                if($svip_coupon_merge != '1' && $use_svip){
                    $coupon['disabled'] = true;
                    continue;
                }
                //店铺券
                if ($valid_total_price >= $coupon['use_min_price']) {
                    if ($useCouponFlag) {
                        if (!in_array($coupon['coupon_user_id'], $merCouponIds)) {
                            continue;
                        }
                    }
                    $use_store_coupon = $coupon;
                    $coupon_price = bcadd($coupon_price, $coupon['coupon_price'], 2);
                    $_pay_price = bcsub($_pay_price, $coupon['coupon_price'], 2);
                    $coupon['checked'] = true;
                } else {
                    $coupon['disabled'] = true;
                }
            }
            unset($coupon);

            $productCouponRate = [];
            $storeCouponRate = null;
            $useCouponIds = [];
            //计算优惠占比
            foreach ($use_coupon_product as $productId => $coupon) {
                $productCouponRate[$productId] = [
                    'rate' => $product_price[$productId] > 0 ? bcdiv($coupon['coupon_price'], $product_price[$productId], 4) : 1,
                    'coupon_price' => $coupon['coupon_price'],
                    'price' => $product_price[$productId]
                ];
                $useCouponIds[] = $coupon['coupon_user_id'];
            }

            if ($use_store_coupon) {
                $storeCouponRate = [
                    'rate' => $pay_price > 0 ? bcdiv($use_store_coupon['coupon_price'], $pay_price, 4) : 1,
                    'coupon_price' => $use_store_coupon['coupon_price'],
                    'price' => $coupon_price
                ];
                $useCouponIds[] = $use_store_coupon['coupon_user_id'];
            }

            //计算单个商品实际支付金额
            foreach ($merchantCart['list'] as $_k => &$cart) {
                $cartTotalPrice = bcmul($this->cartByPrice($cart), $cart['cart_num'], 2);
                $_cartTotalPrice = $cartTotalPrice;
                if (!$cart['product_type'] && $cartTotalPrice > 0) {
                    if (isset($productCouponRate[$cart['product_id']])) {
                        //计算每个商品优惠金额(商品券)
                        if ($productCouponRate[$cart['product_id']]['rate'] >= 1) {
                            $cartTotalPrice = 0;
                        } else {
                            array_pop($product_cart);
                            if (!count($product_cart)) {
                                $cartTotalPrice = bcsub($cartTotalPrice, $productCouponRate[$cart['product_id']]['coupon_price'], 2);
                                $productCouponRate[$cart['product_id']]['coupon_price'] = 0;
                            } else {
                                $couponPrice = bcmul($cartTotalPrice, $productCouponRate[$cart['product_id']]['rate'], 2);
                                $cartTotalPrice = bcsub($cartTotalPrice, $couponPrice, 2);
                                $productCouponRate[$cart['product_id']]['coupon_price'] = bcsub($productCouponRate[$cart['product_id']]['coupon_price'], $couponPrice, 2);
                            }
                        }
                    }

                    //(店铺券)
                    if ($storeCouponRate && $cartTotalPrice > 0) {
                        if ($storeCouponRate['rate'] >= 1) {
                            $cartTotalPrice = 0;
                        } else {
                            if (count($merchantCart['list']) == $_k + 1) {
                                $cartTotalPrice = bcsub($cartTotalPrice, $storeCouponRate['coupon_price'], 2);
                            } else {
                                $couponPrice = bcmul($cartTotalPrice, $storeCouponRate['rate'], 2);
                                $cartTotalPrice = bcsub($cartTotalPrice, $couponPrice, 2);
                                $storeCouponRate['coupon_price'] = bcsub($storeCouponRate['coupon_price'], $couponPrice, 2);
                            }
                        }
                    }
                }

                //单个商品实际支付金额
                $cart['coupon_price'] = bcsub($_cartTotalPrice, $cartTotalPrice, 2);
                $cart['true_price'] = $cartTotalPrice;
            }
            unset($cart, $_k);
            $total_true_price = bcadd($_pay_price, $total_true_price, 2);
            if(count($merchantCartList) > 1 || count($merchantCart['list']) > 1){
                $orderDeliveryStatus = $orderDeliveryStatus && $deliveryStatus;
            }
            $merchantCart['order'] = [
                'true_price' => $_pay_price,
                'platform_coupon_price' => 0,
                'valid_total_price' => $valid_total_price,
                'total_price' => $total_price,
                'final_price' => $final_price,
                'down_price' => $down_price,
                'coupon_price' => $coupon_price,
                'svip_coupon_merge' => $svip_coupon_merge,
                'postage_price' => $postage_price,
                'isTake' => $isTake,
                'total_num' => $total_num,
                'enabledCoupon' => $enabledCoupon,
                'useCouponIds' => $useCouponIds,
                'allow_take' => $merTake,
                'allow_delivery' => $merDelivery,
                'delivery_status' => $deliveryStatus,
                'svip_discount' => $total_svip_discount,
                'use_svip' => $use_svip,
                'order_refund_switch' => $order_refund_switch,
            ];
            $order_total_postage = bcadd($order_total_postage, $postage_price, 2);
            $order_svip_discount = bcadd($total_svip_discount, $order_svip_discount, 2);
            if (count($defaultSort)) {
                $merchantCart['coupon'] = &$defaultSort;
            }
        }
        unset($merchantCart);

        $usePlatformCouponId = $useCoupon[0] ?? 0;
        $usePlatformCouponId = is_array($usePlatformCouponId) ? array_pop($usePlatformCouponId) : $usePlatformCouponId;
        $usePlatformCouponFlag = isset($useCoupon[0]);

        foreach ($merchantCartList as &$merchantCart) {
            if (!$merchantCart['order']['use_svip'])
                continue;
            $totalMergePrice = 0;
            foreach ($merchantCart['list'] as &$cart) {
                if (!$cart['svip_coupon_merge']) {
                    $totalMergePrice = bcadd($totalMergePrice, $cart['true_price'], 2);
                    $cart['allow_price'] = $cart['true_price'];
                }
            }
            unset($cart);
            if ($totalMergePrice > 0) {
                $total_true_price = bcadd($total_true_price, $totalMergePrice, 2);
                $merchantCart['order']['valid_total_price'] = bcadd($merchantCart['order']['valid_total_price'], $totalMergePrice, 2);
                $merchantCart['order']['true_price'] = $merchantCart['order']['valid_total_price'];
            }
        }
        unset($merchantCart);

        //计算平台券优惠金额
//        if ($total_true_price > 0) {
        $StoreCouponUser = app()->make(StoreCouponUserRepository::class);
        $platformCoupon = $StoreCouponUser->validUserPlatformCoupon($uid);
        if ($enabledPlatformCoupon && count($platformCoupon)) {

            $catePriceLst = [];
            $storePriceLst = [];
            $_cartNum = 0;

            foreach ($merchantCartList as &$merchantCart) {
                if ($merchantCart['order']['true_price'] <= 0) continue;
                foreach ($merchantCart['list'] as &$cart) {
                    $_cartNum++;
                    if ($cart['product']['cate_id']) {
                        if (!isset($catePriceLst[$cart['product']['cate_id']])) {
                            $catePriceLst[$cart['product']['cate_id']] = ['price' => 0, 'cart' => []];
                        }
                        $catePriceLst[$cart['product']['cate_id']]['price'] = bcadd($catePriceLst[$cart['product']['cate_id']]['price'], $cart['true_price']);
                        $catePriceLst[$cart['product']['cate_id']]['cart'][] = &$cart;
                    }
                }
                unset($cart);
                $storePriceLst[$merchantCart['mer_id']] = [
                    'price' => $merchantCart['order']['true_price'],
                    'num' => count($merchantCart['list'])
                ];
            }
            unset($merchantCart);
            $flag = false;
            $platformCouponRate = null;

            foreach ($platformCoupon as &$coupon) {
                $coupon['checked'] = false;
                //通用券
                if ($coupon['coupon']['type'] === StoreCouponRepository::TYPE_PLATFORM_ALL) {
                    $coupon['disabled'] = $total_true_price <= 0 || $coupon['use_min_price'] > $total_true_price;
                    if (!$platformCouponRate && !$coupon['disabled'] && !$flag && ((!$usePlatformCouponId && !$usePlatformCouponFlag) || $usePlatformCouponId == $coupon['coupon_user_id'])) {
                        $platformCouponRate = [
                            'id' => $coupon['coupon_user_id'],
                            'type' => $coupon['coupon']['type'],
                            'price' => $total_true_price,
                            'coupon_price' => $coupon['coupon_price'],
                            'use_count' => $_cartNum,
                            'check' => function ($cart) {
                                return true;
                            }
                        ];
                        $coupon['checked'] = true;
                        $flag = true;
                    }
                    //品类券
                } else if ($coupon['coupon']['type'] === StoreCouponRepository::TYPE_PLATFORM_CATE) {
                    $_price = 0;
                    $_use_count = 0;
                    $cateIds = $coupon['product']->column('product_id');
                    $allCateIds = array_unique(array_merge(app()->make(StoreCategoryRepository::class)->allChildren($cateIds), $cateIds));
                    $flag2 = true;
                    foreach ($allCateIds as $cateId) {
                        if (isset($catePriceLst[$cateId])) {
                            $_price = bcadd($catePriceLst[$cateId]['price'], $_price, 2);
                            $_use_count += count($catePriceLst[$cateId]['cart']);
                            $flag2 = false;
                        }
                    }
                    $coupon['disabled'] = $flag2 || $coupon['use_min_price'] > $_price;
                    //品类券可用
                    if (!$platformCouponRate && !$coupon['disabled'] && !$flag && !$flag2 && ((!$usePlatformCouponId && !$usePlatformCouponFlag) || $usePlatformCouponId == $coupon['coupon_user_id'])) {
                        $platformCouponRate = [
                            'id' => $coupon['coupon_user_id'],
                            'type' => $coupon['coupon']['type'],
                            'price' => $_price,
                            'use_cate' => $allCateIds,
                            'coupon_price' => $coupon['coupon_price'],
                            'use_count' => $_use_count,
                            'check' => function ($cart) use ($allCateIds) {
                                return in_array($cart['product']['cate_id'], $allCateIds);
                            }
                        ];
                        $coupon['checked'] = true;
                        $flag = true;
                    }
                    //跨店券
                } else if ($coupon['coupon']['type'] === StoreCouponRepository::TYPE_PLATFORM_STORE) {
                    $_price = 0;
                    $_use_count = 0;
                    $flag2 = true;
                    foreach ($coupon['product'] as $item) {
                        $merId = $item['product_id'];
                        if (isset($storePriceLst[$merId])) {
                            $_price = bcadd($storePriceLst[$merId]['price'], $_price, 2);
                            $_use_count += $storePriceLst[$merId]['num'];
                            $flag2 = false;
                        }
                    }
                    $coupon['disabled'] = $flag2 || $coupon['use_min_price'] > $_price;
                    //店铺券可用
                    if (!$platformCouponRate && !$coupon['disabled'] && !$flag && !$flag2 && ((!$usePlatformCouponId && !$usePlatformCouponFlag) || $usePlatformCouponId == $coupon['coupon_user_id'])) {
                        $_merIds = $coupon['product']->column('product_id');
                        $platformCouponRate = [
                            'id' => $coupon['coupon_user_id'],
                            'type' => $coupon['coupon']['type'],
                            'price' => $_price,
                            'use_store' => $_merIds,
                            'coupon_price' => $coupon['coupon_price'],
                            'use_count' => $_use_count,
                            'check' => function ($cart) use ($_merIds) {
                                return in_array($cart['mer_id'], $_merIds);
                            }
                        ];
                        $coupon['checked'] = true;
                        $flag = true;
                    }
                }
            }
            unset($coupon);
        }
//        }

        $usePlatformCouponId = 0;
        $total_platform_coupon_price = 0;
        //计算平台优惠券
        if (isset($platformCouponRate)) {
            $_coupon_price = $platformCouponRate['coupon_price'];
            foreach ($merchantCartList as &$merchantCart) {
                $_ids = array_column($merchantCart['list'], 'cart_id');
                usort($merchantCart['list'], function ($a, $b) {
                    return $a['true_price'] > $b['true_price'] ? 1 : -1;
                });
                $_price = 0;
                foreach ($merchantCart['list'] as &$cart) {
                    if ($cart['true_price'] <= 0 || !$platformCouponRate['check']($cart)) continue;

                    if ($platformCouponRate['use_count'] === 1) {
                        $couponPrice = min($platformCouponRate['coupon_price'], $cart['true_price']);
                    } else {
                        $couponPrice = min(max(0.01, bcmul($_coupon_price, bcdiv($cart['true_price'], $platformCouponRate['price'], 6), 2)), $cart['true_price']);
                    }
                    $platformCouponRate['coupon_price'] = bcsub($platformCouponRate['coupon_price'], $couponPrice, 2);
                    $cart['true_price'] = bcsub($cart['true_price'], $couponPrice, 2);
                    $cart['platform_coupon_price'] = $couponPrice;
                    $platformCouponRate['use_count']--;
                    $_price = bcadd($couponPrice, $_price, 2);
                }
                unset($cart);
                $merchantCart['order']['platform_coupon_price'] = $_price;
                $merchantCart['order']['true_price'] = bcsub($merchantCart['order']['true_price'], $_price, 2);
                $total_platform_coupon_price = bcadd($total_platform_coupon_price, $_price, 2);
                usort($merchantCart['list'], function ($a, $b) use ($_ids) {
                    return array_search($a['cart_id'], $_ids) > array_search($b['cart_id'], $_ids) ? 1 : -1;
                });
            }
            $usePlatformCouponId = $platformCouponRate['id'];
            unset($merchantCart);
        }

        //积分配置
        $sysIntegralConfig = systemConfig(['integral_money', 'integral_status', 'integral_order_rate']);
        $merIntegralFlag = false;
        $order_total_integral = 0;
        $order_total_integral_price = 0;
        $order_total_give_integral = 0;
        $allow_no_address = true;

        foreach ($merchantCartList as &$merchantCart) {
            $merchantCart['take'] = [
                'mer_integral_rate' => 0,
                'mer_integral_status' => 0,
            ];
            $allow_no_address = $allow_no_address && $merchantCart['order']['isTake'];
            foreach ($merchantCart['config'] as $config) {
                $merchantCart['take'][$config['config_key']] = $config['value'];
            }
            $merIntegralConfig = $merchantCart['take'];
            unset($merchantCart['config']);
            $merIntegralConfig['mer_integral_rate'] = min(1, $merIntegralConfig['mer_integral_rate'] > 0 ? bcdiv($merIntegralConfig['mer_integral_rate'], 100, 4) : $merIntegralConfig['mer_integral_rate']);
            $total_integral = 0;
            $total_integral_price = 0;
            $merIntegralFlag = $merIntegralFlag || ((bool)$merIntegralConfig['mer_integral_status']);
            $integralFlag = $useIntegral && $sysIntegralConfig['integral_status'] && $sysIntegralConfig['integral_money'] > 0 && $merIntegralConfig['mer_integral_status'];

            //计算积分抵扣
            foreach ($merchantCart['list'] as &$cart) {
                //只有普通商品可以抵扣
                if ($cart['product_type'] == 0 && $integralFlag && $userIntegral > 0 && $merchantCart['order']['true_price'] > 0) {
                    $integralRate = $cart['product']['integral_rate'];
                    if ($integralRate < 0) {
                        $integralRate = $merIntegralConfig['mer_integral_rate'];
                    } else if($integralRate > 0){
                        $integralRate = min(bcdiv($integralRate, 100, 4), 1);
                    }
                    if ($integralRate > 0) {
                        $productIntegralPrice = min(bcmul(bcmul($this->cartByPrice($cart), $cart['cart_num'], 2), $integralRate, 2), $cart['true_price']);
                        if ($productIntegralPrice > 0) {
                            $productIntegral = ceil(bcdiv($productIntegralPrice, $sysIntegralConfig['integral_money'], 3));
                            if ($productIntegral <= $userIntegral) {
                                $userIntegral = bcsub($userIntegral, $productIntegral, 0);
                                //使用多少积分抵扣了多少金额
                                $cart['integral'] = [
                                    'use' => $productIntegral,
                                    'price' => $productIntegralPrice
                                ];
                            } else {
                                $productIntegralPrice = bcmul($userIntegral, $sysIntegralConfig['integral_money'], 2);
                                //使用多少积分抵扣了多少金额
                                $cart['integral'] = [
                                    'use' => $userIntegral,
                                    'price' => $productIntegralPrice
                                ];
                                $userIntegral = 0;
                            }

                            $cart['true_price'] = bcsub($cart['true_price'], $cart['integral']['price'], 2);
                            $merchantCart['order']['true_price'] = bcsub($merchantCart['order']['true_price'], $cart['integral']['price'], 2);

                            $total_integral_price = bcadd($total_integral_price, $cart['integral']['price'], 2);
                            $total_integral = bcadd($total_integral, $cart['integral']['use'], 0);
                            continue;
                        }
                    }
                }
                $cart['integral'] = null;
            }
            unset($cart);
            $order_total_integral = bcadd($order_total_integral, $total_integral, 0);
            $order_total_integral_price = bcadd($order_total_integral_price, $total_integral_price, 2);

            $_pay_price = $merchantCart['order']['true_price'];
            $valid_total_price = $merchantCart['order']['valid_total_price'];
            $total_price = $merchantCart['order']['total_price'];
            $final_price = $merchantCart['order']['final_price'];
            $down_price = $merchantCart['order']['down_price'];
            $coupon_price = $merchantCart['order']['coupon_price'];
            $postage_price = $merchantCart['order']['postage_price'];

            //计算订单商品金额
            $org_price = bcadd(bcsub($total_price, $valid_total_price, 2), max($_pay_price, 0), 2);
            if ($presellType == 2) {
                $org_price = max(bcsub($org_price, $final_price, 2), $down_price);
            }

            //获取可优惠金额
            $coupon_price = min($coupon_price, bcsub($total_price, $down_price, 2));
            $order_coupon_price = bcadd($coupon_price, $order_coupon_price, 2);

            //计算订单金额
            if ($order_type != 2 || $presellType != 2) {
                $pay_price = bcadd($postage_price, $org_price, 2);
            } else {
                $pay_price = $org_price;
            }

            $giveIntegralFlag = $sysIntegralConfig['integral_status'] && $sysIntegralConfig['integral_order_rate'] > 0;
            $total_give_integral = 0;
            //计算赠送积分, 只有普通商品赠送积分
            if ($giveIntegralFlag && !$order_type && $pay_price > 0) {
                $total_give_integral = floor(bcmul($pay_price, $sysIntegralConfig['integral_order_rate'], 0));
                if ($total_give_integral > 0 && $svip_status && $svip_integral_rate > 0) {
                    $total_give_integral = bcmul($svip_integral_rate, $total_give_integral, 0);
                }
            }
            $order_total_give_integral = bcadd($total_give_integral, $order_total_give_integral, 0);

            foreach ($fn as $callback) {
                $callback();
            }

            $merchantCart['order']['order_type'] = $order_type;
            $merchantCart['order']['total_give_integral'] = $total_give_integral;
            $merchantCart['order']['total_integral_price'] = $total_integral_price;
            $merchantCart['order']['total_integral'] = $total_integral;
            $merchantCart['order']['org_price'] = $org_price;
            $merchantCart['order']['pay_price'] = $pay_price;
            $merchantCart['order']['coupon_price'] = $coupon_price;

            $order_price = bcadd($order_price, $pay_price, 2);
            $order_total_price = bcadd($order_total_price, $total_price, 2);
        }
        unset($merchantCart);

        if ($order_model) {
            $allow_no_address = false;
        }

        foreach ($merchantCartList as &$merchantCart) {
            foreach ($merchantCart['list'] as &$cart) {
                $cart['total_price'] = bcadd($cart['total_price'], $cart['svip_discount'], 2);
            }
            unset($cart);
            $merchantCart['order']['total_price'] = bcadd($merchantCart['order']['total_price'], $merchantCart['order']['svip_discount'], 2);
            $order_total_price = bcadd($order_total_price, $merchantCart['order']['svip_discount'], 2);
        }
        unset($merchantCart);

        $status = ($address || $order_model || $allow_no_address) ? ($noDeliver ? 'noDeliver' : 'finish') : 'noAddress';
        $order = $merchantCartList;
        $total_price = $order_total_price;
        $openIntegral = $merIntegralFlag && !$order_type && $sysIntegralConfig['integral_status'] && $sysIntegralConfig['integral_money'] > 0;
        $total_coupon = bcadd($order_svip_discount, bcadd(bcadd($total_platform_coupon_price, $order_coupon_price, 2), $order_total_integral_price, 2), 2);
        return compact(
                'order_type',
                'order_model',
                'order_extend',
                'order_total_postage',
                'order_price',
                'total_price',
                'platformCoupon',
                'enabledPlatformCoupon',
                'usePlatformCouponId',
                'order_total_integral',
                'order_total_integral_price',
                'order_total_give_integral',
                'order_svip_discount',
                'total_platform_coupon_price',
                'total_coupon',
                'order_coupon_price',
                'status',
                'address',
                'openIntegral',
                'useIntegral',
                'key',
                'order_refund_switch',
                'order'
            ) + ['allow_address' => !$allow_no_address, 'order_delivery_status' => $orderDeliveryStatus];
    }

    public function v2CreateOrder(int $pay_type, $user, array $cartId, array $extend, array $mark, array $receipt_data, array $takes = null, array $useCoupon = null, bool $useIntegral = false, int $addressId = null, array $post)
    {
        $uid = $user->uid;
        $orderInfo = $this->v2CartIdByOrderInfo($user, $cartId, $takes, $useCoupon, $useIntegral, $addressId, true);
        $order_model = $orderInfo['order_model'];
        $order_extend = $orderInfo['order_extend'];
        if (!$orderInfo['order_delivery_status']) {
            throw new ValidateException('部分商品配送方式不一致,请单独下单');
        }
        if ($orderInfo['order_price'] > 1000000) {
            throw new ValidateException('支付金额超出最大限制');
        }
        if ($orderInfo['status'] == 'noDeliver') throw new ValidateException('部分商品不支持该区域');
        if ($orderInfo['status'] == 'noAddress') throw new ValidateException('请选择地址');
        if (!$order_model && $orderInfo['allow_address']) {
            if (!$orderInfo['address']) throw new ValidateException('请选择正确的收货地址');
            if (!$orderInfo['address']['province_id']) throw new ValidateException('请完善收货地址信息');
            $extend = [];
        } else if (count($order_extend)) {
            $extend = app()->make(OrderVirtualFieldValidate::class)->load($order_extend, $extend);
        } else {
            $extend = [];
        }
        $orderType = $orderInfo['order_type'];
        if ($orderType && (count($orderInfo['order']) > 1 || ($orderType != 10 && count($orderInfo['order'][0]['list']) > 1))) {
            throw new ValidateException('活动商品请单独购买');
        }

        $merchantCartList = $orderInfo['order'];
        $cartSpread = 0;
        $hasTake = false;

        foreach ($merchantCartList as $merchantCart) {
            if ($merchantCart['order']['isTake']) {
                $hasTake = true;
            }
            //检查发票状态
            if (isset($receipt_data[$merchantCart['mer_id']]) && !$merchantCart['openReceipt'])
                throw new ValidateException('该店铺不支持开发票');

            foreach ($merchantCart['list'] as $cart) {
                if (!$cartSpread && $cart['spread_id']) {
                    $cartSpread = $cart['spread_id'];
                }
            }
        }
        if ($hasTake) {
            app()->make(UserAddressValidate::class)->scene('take')->check($post);
        }

        if ($cartSpread) {
            app()->make(UserRepository::class)->bindSpread($user, $cartSpread);
        }

        $isSelfBuy = $user->is_promoter && systemConfig('extension_self') ? 1 : 0;
        if ($isSelfBuy) {
            $spreadUser = $user;
            $topUser = $user->valid_spread;
        } else {
            $spreadUser = $user->valid_spread;
            $topUser = $user->valid_top;
        }
        $spreadUid = $spreadUser->uid ?? 0;
        $topUid = $topUser->uid ?? 0;

        $merchantRepository = app()->make(MerchantRepository::class);
        $giveCouponIds = [];
        $ex = systemConfig('extension_status');
        $address = $orderInfo['address'];
        $allUseCoupon = $orderInfo['usePlatformCouponId'] ? [$orderInfo['usePlatformCouponId']] : [];
        $totalNum = 0;
        $totalPostage = 0;
        $totalCost = 0;
        $cartIds = [];
        $orderList = [];

        foreach ($merchantCartList as $k => $merchantCart) {
            $cost = 0;
            $total_extension_one = 0;
            $total_extension_two = 0;
            //计算佣金和赠送的优惠券
            foreach ($merchantCart['list'] as &$cart) {
                $cartIds[] = $cart['cart_id'];
                $giveCouponIds = array_merge($giveCouponIds, $cart['product']['give_coupon_ids'] ?: []);
                $cart['cost'] = $cart['productAttr']['cost'];
                $cost = bcadd(bcmul($cart['cost'], $cart['cart_num'], 2), $cost, 2);
                $extension_one = 0;
                $extension_two = 0;
                if ($ex) {
                    //预售订单
                    if ($orderType == 2) {
                        $_payPrice = $merchantCart['order']['pay_price'];
                        $rate = $cart['productPresell']['presell_type'] == 2 ? bcdiv($cart['productPresellAttr']['down_price'], $cart['productPresellAttr']['presell_price'], 3) : 1;
                        $one_price = $_payPrice > 0 ? bcdiv($_payPrice, $cart['cart_num'], 2) : 0;
                        if ($spreadUid && $cart['productPresellAttr']['bc_extension_one'] > 0) {
                            $org_extension = $cart['productPresellAttr']['bc_extension_one'];
                            if ($spreadUser->brokerage_level > 0 && $spreadUser->brokerage && $spreadUser->brokerage->extension_one_rate > 0) {
                                $org_extension = bcmul($org_extension, 1 + $spreadUser->brokerage->extension_one_rate, 2);
                            }
                            $_extension_one = bcmul($rate, $org_extension, 3);
                            $presell_extension_one = 0;
                            if ($cart['true_price'] > 0) {
                                $extension_one = bcmul(bcdiv($one_price, $cart['productPresellAttr']['down_price'], 3), $_extension_one, 2);
                            }
                            if ($rate < 1) {
                                $presell_extension_one = bcmul(1 - $rate, $org_extension, 2);
                            }
                            $cart['final_extension_one'] = bcmul($extension_one, $cart['cart_num'], 2);
                            $extension_one = bcadd($extension_one, $presell_extension_one, 2);
                            $cart['presell_extension_one'] = bcmul($presell_extension_one, $cart['cart_num'], 2);
                        }
                        if ($topUid && $cart['productPresellAttr']['bc_extension_two'] > 0) {
                            $org_extension = $cart['productPresellAttr']['bc_extension_two'];
                            if ($topUser->brokerage_level > 0 && $topUser->brokerage && $topUser->brokerage->extension_two_rate > 0) {
                                $org_extension = bcmul($org_extension, 1 + $topUser->brokerage->extension_two_rate, 2);
                            }
                            $_extension_two = bcmul($rate, $org_extension, 2);
                            $presell_extension_two = 0;
                            if ($cart['true_price'] > 0) {
                                $extension_two = bcmul(bcdiv($one_price, $cart['productPresellAttr']['down_price'], 3), $_extension_two, 2);
                            }
                            if ($rate < 1) {
                                $presell_extension_two = bcmul(1 - $rate, $org_extension, 2);
                            }
                            $cart['final_extension_two'] = bcmul($extension_two, $cart['cart_num'], 2);;
                            $extension_two = bcadd($extension_two, $presell_extension_two, 2);
                            $cart['presell_extension_two'] = bcmul($presell_extension_two, $cart['cart_num'], 2);
                        }
                    } else if (!$orderType) {
                        if ($spreadUid && $cart['productAttr']['bc_extension_one'] > 0) {
                            $org_extension = $cart['productAttr']['bc_extension_one'];
                            if ($spreadUser->brokerage_level > 0 && $spreadUser->brokerage && $spreadUser->brokerage->extension_one_rate > 0) {
                                $org_extension = bcmul($org_extension, 1 + $spreadUser->brokerage->extension_one_rate, 2);
                            }
                            $extension_one = $cart['true_price'] > 0 ? bcmul(bcdiv($cart['true_price'], $cart['total_price'], 3), $org_extension, 2) : 0;
                        }
                        if ($topUid && $cart['productAttr']['bc_extension_two'] > 0) {
                            $org_extension = $cart['productAttr']['bc_extension_two'];
                            if ($topUser->brokerage_level > 0 && $topUser->brokerage && $topUser->brokerage->extension_two_rate > 0) {
                                $org_extension = bcmul($org_extension, 1 + $topUser->brokerage->extension_two_rate, 2);
                            }
                            $extension_two = $cart['true_price'] > 0 ? bcmul(bcdiv($cart['true_price'], $cart['total_price'], 3), $org_extension, 2) : 0;
                        }
                    }
                }
                $cart['extension_one'] = $extension_one;
                $cart['extension_two'] = $extension_two;
                $total_extension_one = bcadd($total_extension_one, bcmul($extension_one, $cart['cart_num'], 2), 2);
                $total_extension_two = bcadd($total_extension_two, bcmul($extension_two, $cart['cart_num'], 2), 2);
            }
            unset($cart);

            $rate = 0;
            if ($merchantCart['commission_rate'] > 0) {
                $rate = $merchantCart['commission_rate'];
            } else if (isset($merchantCart['merchantCategory']['commission_rate']) && $merchantCart['merchantCategory']['commission_rate'] > 0) {
                $rate = bcmul($merchantCart['merchantCategory']['commission_rate'], 100, 4);
            }
            $user_address = isset($address) ? ($address['province'] . $address['city'] . $address['district'] . $address['street'] . $address['detail']) : '';
            //整理订单数据
            $_order = [
                'cartInfo' => $merchantCart,
                'activity_type' => $orderInfo['order_type'],
                'commission_rate' => (float)$rate,
                'order_type' => $merchantCart['order']['isTake'] ? 1 : 0,
                'is_virtual' => $order_model,
                'extension_one' => $total_extension_one,
                'extension_two' => $total_extension_two,
                'order_sn' => $this->getNewOrderId(StoreOrderRepository::TYPE_SN_ORDER) . ($k + 1),
                'uid' => $uid,
                'spread_uid' => $spreadUid,
                'top_uid' => $topUid,
                'is_selfbuy' => $isSelfBuy,
                'real_name' => $merchantCart['order']['isTake'] ? $post['real_name'] : ($address['real_name'] ?? ''),
                'user_phone' => $merchantCart['order']['isTake'] ? $post['phone'] : ($address['phone'] ?? ''),
                'user_address' => $user_address,
                'cart_id' => implode(',', array_column($merchantCart['list'], 'cart_id')),
                'total_num' => $merchantCart['order']['total_num'],
                'total_price' => $merchantCart['order']['total_price'],
                'total_postage' => $merchantCart['order']['postage_price'],
                'pay_postage' => $merchantCart['order']['postage_price'],
                'svip_discount' => $merchantCart['order']['svip_discount'],
                'pay_price' => $merchantCart['order']['pay_price'],
                'integral' => $merchantCart['order']['total_integral'],
                'integral_price' => $merchantCart['order']['total_integral_price'],
                'give_integral' => $merchantCart['order']['total_give_integral'],
                'mer_id' => $merchantCart['mer_id'],
                'cost' => $cost,
                'order_extend' => count($extend) ? json_encode($extend, JSON_UNESCAPED_UNICODE) : '',
                'coupon_id' => implode(',', $merchantCart['order']['useCouponIds']),
                'mark' => $mark[$merchantCart['mer_id']] ?? '',
                'coupon_price' => bcadd($merchantCart['order']['coupon_price'], $merchantCart['order']['platform_coupon_price'], 2),
                'platform_coupon_price' => $merchantCart['order']['platform_coupon_price'],
                'pay_type' => $pay_type,
                'refund_switch' => $merchantCart['order']['order_refund_switch'],
            ];
            $allUseCoupon = array_merge($allUseCoupon, $merchantCart['order']['useCouponIds']);
            $orderList[] = $_order;
            $totalPostage = bcadd($totalPostage, $_order['total_postage'], 2);
            $totalCost = bcadd($totalCost, $cost, 2);
            $totalNum += $merchantCart['order']['total_num'];
        }
        $groupOrder = [
            'uid' => $uid,
            'group_order_sn' => count($orderList) === 1 ? $orderList[0]['order_sn'] : ($this->getNewOrderId(StoreOrderRepository::TYPE_SN_ORDER) . '0'),
            'total_postage' => $totalPostage,
            'total_price' => $orderInfo['total_price'],
            'total_num' => $totalNum,
            'real_name' => $address['real_name'] ?? '',
            'user_phone' => $address['phone'] ?? '',
            'user_address' => $user_address,
            'pay_price' => $orderInfo['order_price'],
            'coupon_price' => bcadd($orderInfo['total_platform_coupon_price'], $orderInfo['order_coupon_price'], 2),
            'pay_postage' => $totalPostage,
            'cost' => $totalCost,
            'coupon_id' => $orderInfo['usePlatformCouponId'] > 0 ? $orderInfo['usePlatformCouponId'] : '',
            'pay_type' => $pay_type,
            'give_coupon_ids' => $giveCouponIds,
            'integral' => $orderInfo['order_total_integral'],
            'integral_price' => $orderInfo['order_total_integral_price'],
            'give_integral' => $orderInfo['order_total_give_integral'],
            'activity_type' => $orderInfo['order_type'],
        ];
        event('order.create.before', compact('groupOrder', 'orderList'));
        $group = Db::transaction(function () use ($ex, $user, $topUid, $spreadUid, $uid, $receipt_data, $cartIds, $allUseCoupon, $groupOrder, $orderList, $orderInfo) {
            $storeGroupOrderRepository = app()->make(StoreGroupOrderRepository::class);
            $storeCartRepository = app()->make(StoreCartRepository::class);
            $attrValueRepository = app()->make(ProductAttrValueRepository::class);
            $productRepository = app()->make(ProductRepository::class);
            $storeOrderProductRepository = app()->make(StoreOrderProductRepository::class);
            $couponUserRepository = app()->make(StoreCouponUserRepository::class);
            //订单记录
            $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
            $userMerchantRepository = app()->make(UserMerchantRepository::class);

            //减库存
            foreach ($orderList as $order) {
                foreach ($order['cartInfo']['list'] as $cart) {
                    if (!isset($uniqueList[$cart['productAttr']['product_id'] . $cart['productAttr']['unique']]))
                        $uniqueList[$cart['productAttr']['product_id'] . $cart['productAttr']['unique']] = true;
                    else
                        throw new ValidateException('购物车商品信息重复');

                    try {
                        if ($cart['product_type'] == '1') {
                            $attrValueRepository->descSkuStock($cart['product']['old_product_id'], $cart['productAttr']['sku'], $cart['cart_num']);
                            $productRepository->descStock($cart['product']['old_product_id'], $cart['cart_num']);
                        } else if ($cart['product_type'] == '2') {
                            $productPresellSkuRepository = app()->make(ProductPresellSkuRepository::class);
                            $productPresellSkuRepository->descStock($cart['productPresellAttr']['product_presell_id'], $cart['productPresellAttr']['unique'], $cart['cart_num']);
                            $attrValueRepository->descStock($cart['productAttr']['product_id'], $cart['productAttr']['unique'], $cart['cart_num']);
                            $productRepository->descStock($cart['product']['product_id'], $cart['cart_num']);
                        } else if ($cart['product_type'] == '3') {
                            app()->make(ProductAssistSkuRepository::class)->descStock($cart['productAssistAttr']['product_assist_id'], $cart['productAssistAttr']['unique'], $cart['cart_num']);
                            $productRepository->descStock($cart['product']['old_product_id'], $cart['cart_num']);
                            $attrValueRepository->descStock($cart['product']['old_product_id'], $cart['productAttr']['unique'], $cart['cart_num']);
                        } else if ($cart['product_type'] == '4') {
                            app()->make(ProductGroupSkuRepository::class)->descStock($cart['activeSku']['product_group_id'], $cart['activeSku']['unique'], $cart['cart_num']);
                            $productRepository->descStock($cart['product']['old_product_id'], $cart['cart_num']);
                            $attrValueRepository->descStock($cart['product']['old_product_id'], $cart['productAttr']['unique'], $cart['cart_num']);
                        } else {
                            $attrValueRepository->descStock($cart['productAttr']['product_id'], $cart['productAttr']['unique'], $cart['cart_num']);
                            $productRepository->descStock($cart['product']['product_id'], $cart['cart_num']);
                            if ($cart['integral'] && $cart['integral']['use'] > 0) {
                                $productRepository->incIntegral($cart['product']['product_id'], $cart['integral']['use'], $cart['integral']['price']);
                            }
                        }
                    } catch (\Exception $e) {
                        throw new ValidateException('库存不足');
                    }
                }
            }

            if ($orderInfo['order_type'] == 10 && !app()->make(StoreDiscountRepository::class)->decStock($orderList[0]['cartInfo']['list'][0]['source_id'])) {
                throw new ValidateException('套餐库不足');
            }

            //修改购物车状态
            $storeCartRepository->updates($cartIds, [
                'is_pay' => 1
            ]);

            //使用优惠券
            if (count($allUseCoupon)) {
                $couponUserRepository->updates($allUseCoupon, [
                    'use_time' => date('Y-m-d H:i:s'),
                    'status' => 1
                ]);
            }

            //创建订单
            $groupOrder = $storeGroupOrderRepository->create($groupOrder);
            $bills = [];

            if ($groupOrder['integral'] > 0) {
                $user->integral = bcsub($user->integral, $groupOrder['integral'], 0);
                app()->make(UserBillRepository::class)->decBill($user['uid'], 'integral', 'deduction', [
                    'link_id' => $groupOrder['group_order_id'],
                    'status' => 1,
                    'title' => '购买商品',
                    'number' => $groupOrder['integral'],
                    'mark' => '购买商品使用积分抵扣' . floatval($groupOrder['integral_price']) . '元',
                    'balance' => $user->integral
                ]);
                $user->save();
            }

            foreach ($orderList as $k => $order) {
                $orderList[$k]['group_order_id'] = $groupOrder->group_order_id;
            }

            $orderProduct = [];
            $orderStatus = [];
            foreach ($orderList as $order) {
                $cartInfo = $order['cartInfo'];
                unset($order['cartInfo']);
                //创建子订单
                $_order = $this->dao->create($order);

                if ($order['integral'] > 0) {
                    $bills[] = [
                        'uid' => $uid,
                        'link_id' => $_order->order_id,
                        'pm' => 0,
                        'title' => '积分抵扣',
                        'category' => 'mer_integral',
                        'type' => 'deduction',
                        'number' => $order['integral'],
                        'balance' => $user->integral,
                        'mark' => '购买商品使用' . $order['integral'] . '积分抵扣' . floatval($order['integral_price']) . '元',
                        'mer_id' => $order['mer_id'],
                        'status' => 1
                    ];
                }

                //创建发票信息
                if (isset($receipt_data[$_order['mer_id']])) {
                    app()->make(StoreOrderReceiptRepository::class)->add($receipt_data[$_order['mer_id']], $_order);
                }

                $orderStatus[] = [
                    'order_id' => $_order->order_id,
                    'order_sn' => $_order->order_sn,
                    'type' => $storeOrderStatusRepository::TYPE_ORDER,
                    'change_message' => '订单生成',
                    'change_type' => $storeOrderStatusRepository::ORDER_STATUS_CREATE,
                    'uid' => $user->uid,
                    'nickname' => $user->nickname,
                    'user_type' => $storeOrderStatusRepository::U_TYPE_USER,
                ];

                foreach ($cartInfo['list'] as $cart) {

                    $productPrice = $cart['true_price'];
                    $extension_one = $cart['extension_one'];
                    $extension_two = $cart['extension_two'];

                    //计算预售订单尾款
                    if ($cartInfo['order']['order_type'] == 2) {
                        $finalPrice = max(bcsub($cartInfo['order']['final_price'], $cartInfo['order']['coupon_price'], 2), 0);
                        $allFinalPrice = $order['order_type'] ? $finalPrice : bcadd($finalPrice, $order['pay_postage'], 2);
                        if ($cart['productPresell']['presell_type'] == 1) {
                            $productPrice = bcsub($cartInfo['order']['pay_price'], $order['pay_postage'], 2);
                        } else {
                            $productPrice = bcadd($cartInfo['order']['pay_price'], $finalPrice, 2);
                        }
                        //生成尾款订单
                        if ($cart['productPresell']['presell_type'] == 2) {
                            $presellOrderRepository = app()->make(PresellOrderRepository::class);
                            $presellOrderRepository->create([
                                'uid' => $uid,
                                'order_id' => $_order->order_id,
                                'mer_id' => $_order->mer_id,
                                'final_start_time' => $cart['productPresell']['final_start_time'],
                                'final_end_time' => $cart['productPresell']['final_end_time'],
                                'pay_price' => $allFinalPrice,
                                'presell_order_sn' => $this->getNewOrderId(StoreOrderRepository::TYPE_SN_PRESELL)
                            ]);
                        }
                        app()->make(ProductPresellSkuRepository::class)->incCount($cart['source_id'], $cart['productAttr']['unique'], 'one_take');
                    }

                    $order_cart = [
                        'product' => $cart['product'],
                        'productAttr' => $cart['productAttr'],
                        'product_type' => $cart['product_type']
                    ];

                    if ($cart['product_type'] == '2') {
                        $order_cart['productPresell'] = $cart['productPresell'];
                        $order_cart['productPresellAttr'] = $cart['productPresellAttr'];
                        $order_cart['final_extension_one'] = $cart['final_extension_one'] ?? 0;
                        $order_cart['final_extension_two'] = $cart['final_extension_two'] ?? 0;
                        $order_cart['presell_extension_one'] = $cart['presell_extension_one'] ?? 0;
                        $order_cart['presell_extension_two'] = $cart['presell_extension_two'] ?? 0;
                    } else if ($cart['product_type'] == '3') {
                        $order_cart['productAssistAttr'] = $cart['productAssistAttr'];
                        $order_cart['productAssistSet'] = $cart['productAssistSet'];
                    } else if ($cart['product_type'] == '4') {
                        $order_cart['activeSku'] = $cart['activeSku'];
                    } else if ($cart['product_type'] == '10') {
                        $order_cart['active'] = $cart['productDiscount'];
                        $order_cart['activeSku'] = $cart['productDiscountAttr'];
                    }

                    $orderProduct[] = [
                        'order_id' => $_order->order_id,
                        'cart_id' => $cart['cart_id'],
                        'uid' => $uid,
                        'product_id' => $cart['product_id'],
                        'activity_id' => $cart['source'] >= 2 ? $cart['source_id'] : $cart['product_id'],
                        'total_price' => $cart['total_price'],
                        'product_price' => $productPrice,
                        'extension_one' => $extension_one,
                        'extension_two' => $extension_two,
                        'postage_price' => $cart['postage_price'],
                        'svip_discount' => $cart['svip_discount'],
                        'cost' => $cart['cost'],
                        'coupon_price' => $cart['coupon_price'],
                        'platform_coupon_price' => $cart['platform_coupon_price'],
                        'product_sku' => $cart['productAttr']['unique'],
                        'product_num' => $cart['cart_num'],
                        'refund_num' => $cart['cart_num'],
                        'integral_price' => $cart['integral']['price'] ?? 0,
                        'integral' => $cart['integral'] ? bcdiv($cart['integral']['use'], $cart['cart_num'], 0) : 0,
                        'integral_total' => $cart['integral'] ? $cart['integral']['use'] : 0,
                        'product_type' => $cart['product_type'],
                        'cart_info' => json_encode($order_cart),
                        'refund_switch' => $cart['refund_switch'],
                    ];
                }

                $userMerchantRepository->getInfo($uid, $order['mer_id']);
                app()->make(MerchantRepository::class)->incSales($order['mer_id'], $order['total_num']);
            }

            if (count($bills) > 0) {
                app()->make(UserBillRepository::class)->insertAll($bills);
            }
            $storeOrderStatusRepository->batchCreateLog($orderStatus);
            $storeOrderProductRepository->insertAll($orderProduct);
            event('order.create', compact('groupOrder'));
            return $groupOrder;
        });
        foreach ($merchantCartList as $merchantCart) {
            foreach ($merchantCart['list'] as $cart) {
                if (($cart['productAttr']['stock'] - $cart['cart_num']) < (int)merchantConfig($merchantCart['mer_id'], 'mer_store_stock')) {
                    SwooleTaskService::merchant('notice', [
                        'type' => 'min_stock',
                        'data' => [
                            'title' => '库存不足',
                            'message' => $cart['product']['store_name'] . '(' . $cart['productAttr']['sku'] . ')库存不足',
                            'id' => $cart['product']['product_id']
                        ]
                    ], $merchantCart['mer_id']);
                }
            }
        }
        Queue::push(SendSmsJob::class, ['tempId' => 'ORDER_CREATE', 'id' => $group->group_order_id]);
        return $group;
    }
}
