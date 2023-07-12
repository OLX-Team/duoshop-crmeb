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

namespace crmeb\services;

use app\common\repositories\store\broadcast\BroadcastRoomRepository;
use app\common\repositories\store\order\StoreGroupOrderRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\order\StoreRefundOrderRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\ProductTakeRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use crmeb\services\sms\Sms;
use think\facade\Cache;

class SmsService
{
    const SMS_YUNXIN = 1;
    const SMS_ALIYUN = 2;

    public static function create()
    {
        $gateway = (int)systemConfig('sms_use_type') ?: 1;
        switch ($gateway) {
            case 1:
                $name = 'yunxin';
                $config = [
                    'account' => systemConfig('serve_account'),
                    'secret' => systemConfig('serve_token')
                ];
                break;
            case 2:
                $name = 'aliyun';
                $config = [];
                break;
        }
        return new Sms($name, $config);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/9/19
     * @param $phone
     * @param $code
     * @param $type
     * @return bool
     */
    public function checkSmsCode($phone, $code, $type)
    {
        if (!env('DEVELOPMENT',false)) {
            $sms_key = $this->sendSmsKey($phone, $type);
            if (!$cache_code = Cache::get($sms_key)) return false;
            if ($code != $cache_code) return false;
            Cache::delete($sms_key);
        }
        return true;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/9/19
     * @param $phone
     * @param string $type
     * @return string
     */
    public function sendSmsKey($phone, $type = 'login')
    {
        switch ($type) {
            case 'login': //登录
                return 'api_login_' . $phone;
                break;
            case 'binding': //绑定手机号
                return 'api_binding_' . $phone;
                break;
            case 'intention': //申请入住
                return 'merchant_intention_' . $phone;
                break;
            case 'change_pwd': //修改密码
                return 'change_pwd_' . $phone;
                break;
            case 'change_phone': //修改手机号
                return 'change_phone_' . $phone;
                break;
            default:
                return 'crmeb_' . $phone;
                break;
        }
    }

    public static function sendMessage($data)
    {
        $tempId = $data['tempId'];
        $id = $data['id'];
        switch ($tempId) {
                //发货提醒 -2.1
            case 'DELIVER_GOODS_CODE':
                $order = app()->make(StoreOrderRepository::class)->get($id);
                if (!$order || !$order->user_phone) return;
                $nickname = $order->user->nickname;
                $store_name = $order->orderProduct[0]['cart_info']['product']['store_name'] . (count($order->orderProduct) ? '等' : '');
                $order_id = $order->order_sn;

                self::create()->send($order->user_phone, $tempId, compact('nickname', 'store_name', 'order_id'));
                break;
                //确认收货短信提醒 -2.1
            case 'ORDER_TAKE_SUCCESS':
                $order = app()->make(StoreOrderRepository::class)->get($id);
                if (!$order || !$order->user_phone) return;
                $order_id = $order->order_sn;
                $store_name = $order->orderProduct[0]['cart_info']['product']['store_name'] . (count($order->orderProduct) ? '等' : '');

                self::create()->send($order->user_phone, $tempId, compact('store_name', 'order_id'));
                break;
                //用户支付成功提醒 -2.1
            case 'ORDER_PAY_SUCCESS':
                $order = app()->make(StoreGroupOrderRepository::class)->get($id);
                $pay_price = $order->pay_price;
                $order_id = $order->group_order_sn;
                self::create()->send($order->user_phone, $tempId, compact('pay_price', 'order_id'));
                break;
                //改价提醒 -2.1
            case 'PRICE_REVISION_CODE':
                $order = app()->make(StoreOrderRepository::class)->get($id);
                if (!$order || !$order->user_phone) return;
                $pay_price = $order->pay_price;
                $order_id = $order->order_sn;
                self::create()->send($order->user_phone, $tempId, compact('pay_price', 'order_id'));
                break;
                //提醒付款通知 -2.1
            case 'ORDER_PAY_FALSE':
                $order = app()->make(StoreGroupOrderRepository::class)->get($id);
                if (!$order || !$order->user_phone) return;
                $order_id = $order->group_order_sn;

                self::create()->send($order->user_phone, $tempId, compact('order_id'));
                break;
                //商家拒绝退款提醒 -2.1
            case 'REFUND_FAIL_CODE':
                $order = app()->make(StoreRefundOrderRepository::class)->get($id);
                if (!$order || !$order->order->user_phone) return;
                $order_id = $order->order->order_sn;
                $store_name = $order->refundProduct[0]->product['cart_info']['product']['store_name'] . (count($order->refundProduct) ? '等' : '');

                self::create()->send($order->order->user_phone, $tempId, compact('order_id', 'store_name'));
                break;
                //商家同意退款提醒  -2.1
            case 'REFUND_SUCCESS_CODE':
                //notbreak;
                //退款确认提醒  -2.1
            case 'REFUND_CONFORM_CODE':
                $order = app()->make(StoreRefundOrderRepository::class)->get($id);
                if (!$order || !$order->order->user_phone) return;
                $order_id = $order->order->order_sn;
                $store_name = $order->refundProduct[0]->product['cart_info']['product']['store_name'] . (count($order->refundProduct) ? '等' : '');

                self::create()->send($order->order->user_phone, $tempId, compact('order_id', 'store_name'));
                break;
                //管理员 支付成功提醒 -2.1
            case 'ADMIN_PAY_SUCCESS_CODE':
                $order = app()->make(StoreGroupOrderRepository::class)->get($id);
                if (!$order) return;
                foreach ($order->orderList as $_order) {
                    self::sendMerMessage($_order->mer_id, $tempId, ['order_id' => $_order->order_sn]);
                }
                break;
                //管理员退款单提醒 -2.1
            case 'ADMIN_RETURN_GOODS_CODE':
                //notbreak
                //退货信息提醒
            case 'ADMIN_DELIVERY_CODE':
                $order = app()->make(StoreRefundOrderRepository::class)->get($id);
                if (!$order) return;
                self::sendMerMessage($order->mer_id, $tempId, ['order_id' => $order->refund_order_sn]);
                break;
                //管理员确认收货提醒 2.1
            case 'ADMIN_TAKE_DELIVERY_CODE':
                $order = app()->make(StoreOrderRepository::class)->get($id);
                if (!$order) return;
                self::sendMerMessage($order->mer_id, $tempId, ['order_id' => $order->order_sn]);
                break;
                //直播审核通过主播通知 2.1
            case 'BROADCAST_ROOM_CODE':
                $room = app()->make(BroadcastRoomRepository::class)->get($id);
                if (!$room) return;
                self::create()->send($room->phone, $tempId, [
                    'wechat' => $room->anchor_wechat,
                    'date' => date('Y年m月d日 H时i分', strtotime($room->start_time))
                ]);
                break;
                //直播未通过通知 2.1
            case 'BROADCAST_ROOM_FAIL':
                $room = app()->make(BroadcastRoomRepository::class)->get($id);
                if (!$room) return;
                self::create()->send($room->phone, $tempId, [
                    'wechat' => $room->anchor_wechat
                ]);
                break;
                //预售尾款支付通知 2.1
            case 'PAY_PRESELL_CODE':
                $order = app()->make(StoreOrderRepository::class)->get($id);
                if (!$order || !$order->user_phone || !$order->pay_time) return;
                self::create()->send($order->user_phone, $tempId, [
                    'date' => date('Y-m-d', strtotime($order->pay_time)),
                    'product_name' => $order->orderProduct[0]['cart_info']['product']['store_name'] ?? ''
                ]);
                break;
                //入驻申请通过提醒 2.1
            case 'APPLY_MER_SUCCESS':
                self::create()->send($id['phone'], $tempId, [
                    'date' => $id['date'],
                    'mer' => $id['mer'],
                    'phone' => $id['phone'],
                    'pwd' => $id['pwd'],
                    'site_name' => systemConfig('site_name'),
                ]);
                break;
                //入驻申请未通过提醒 2.1
            case 'APPLY_MER_FAIL':
                self::create()->send($id['phone'], $tempId, [
                    'date' => $id['date'],
                    'mer' => $id['mer'],
                    'site' => systemConfig('site_name'),
                ]);
                break;
                //到货提醒通知 2.1
            case 'PRODUCT_INCREASE':
                $product = app()->make(ProductRepository::class)->getWhere(['product_id' => $id], '*', ['attrValue']);
                if (!$product) return ;
                $unique[] = 1;
                foreach ($product['attrValue'] as $item) {
                    if ($item['stock'] > 0) $unique[] = $item['unique'];
                }
                $make = app()->make(ProductTakeRepository::class);
                $query = $make->getSearch(['product_id' => $id, 'status' => 0, 'type' => 1])->where('unique', 'in', $unique);
                $ret = $query->select();
                if (!$ret) return ;
                foreach ($ret as $item) {
                    if ($item->user->phone) {
                        self::create()->send($item->user->phone, $tempId, [
                            'product' => $product->store_name,
                            'site' => systemConfig('site_name'),
                        ]);
                        $tak_id[] = $item->product_take_id;
                    }
                }
                if (!empty($tak_id)) app()->make(ProductTakeRepository::class)->updates($tak_id, ['status' => 1]);
                break;
                //积分即将到期提醒 2.1
            case 'INTEGRAL_INVALID':
                self::create()->send($id['phone'], $tempId, [
                    'integral' => $id['integral'],
                    'date' => date('m月d日', strtotime($id['date'])),
                    'site' => systemConfig('site_name'),
                ]);
                break;
                //保证金退回申请通过通知 2.1
            case 'REFUND_MARGIN_SUCCESS':
                //nobreak;
                //保证金退回申请未通过通知 2.1
            case 'REFUND_MARGIN_FAIL':
                self::create()->send($id['phone'], $tempId, ['name' => $id['name'], 'time' => $id['time'],]);
                break;
                //分账商户申请通过 2.1
            case 'APPLYMENTS_SUCCESS':
                //nobreak;
                //商户申请分账待验证
            case 'APPLYMENTS_SIGN':
                //nobreak;
                //商户申请分账未通过
            case 'APPLYMENTS_FAIL':
                self::create()->send($id['phone'], $tempId, ['mer_name' => $id['mer_name']]);
                break;
                //付费会员支付成功
            case 'SVIP_PAY_SUCCESS':
                self::create()->send($id['phone'], $tempId, ['store_name' => systemConfig('site_name'),'date' => $id['date']]);
                break;
        }
    }

    public static function sendMerMessage($merId, string $tempId, array $data)
    {
        $noticeServiceInfo = app()->make(StoreServiceRepository::class)->getNoticeServiceInfo($merId);
        $yunxinSmsService = self::create();
        foreach ($noticeServiceInfo as $service) {
            if (!$service['phone']) continue;
            $yunxinSmsService->send($service['phone'], $tempId, array_merge(['admin_name' => $service['nickname']], $data));
        }
    }


}
