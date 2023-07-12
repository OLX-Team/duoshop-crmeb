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

use app\common\repositories\store\order\StoreGroupOrderRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\order\StoreOrderStatusRepository;
use app\common\repositories\store\order\StoreRefundOrderRepository;
use app\common\repositories\store\product\ProductGroupBuyingRepository;
use app\common\repositories\store\product\ProductGroupUserRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\ProductTakeRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\system\notice\SystemNoticeConfigRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserExtractRepository;
use app\common\repositories\user\UserRechargeRepository;
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\listens\pay\UserRechargeSuccessListen;
use crmeb\services\template\Template;
use app\common\repositories\user\UserRepository;
use think\facade\Log;
use think\facade\Route;

class WechatTemplateMessageService
{
    /**
     * TODO
     * @param array $data
     * @param string|null $link
     * @param string|null $color
     * @return bool
     * @author Qinii
     * @day 2020-06-29
     */
    public  function sendTemplate(array $data)
    {
        event('wechat.template.before',compact('data'));
        $res = $this->templateMessage($data);
        if(!$res || !is_array($res))
            return true;
        foreach($res as $item){
            if(is_array($item['uid'])){
                foreach ($item['uid'] as $value){
                    $openid = $this->getUserOpenID($value['uid']);
                    if (!$openid) continue;
                    $this->send($openid,$item['tempCode'],$item['data'],'wechat',$item['link'],$item['color']);
                }
            }else{
                $openid = $this->getUserOpenID($item['uid']);
                if (!$openid) continue;
                $this->send($openid,$item['tempCode'],$item['data'],'wechat',$item['link'],$item['color']);
            }
        }
        event('wechat.template',compact('res'));
    }

    /**
     * TODO
     * @param $data
     * @param string|null $link
     * @param string|null $color
     * @return bool
     * @author Qinii
     * @day 2020-07-01
     */
    public function subscribeSendTemplate($data)
    {
        event('wechat.subscribeTemplate.before',compact('data'));
        $res = $this->subscribeTemplateMessage($data);
        Log::info('订阅消息发送Data' . var_export($data, 1));
        if(!$res || !is_array($res))return true;

        foreach($res as $item){
            if(is_array($item['uid'])){
                foreach ($item['uid'] as $value){
                    $openid = $this->getUserOpenID($value,'min');
                    if (!$openid) {
                        continue;
                    }
                    $this->send($openid,$item['tempCode'],$item['data'],'subscribe',$item['link'],$item['color']);
                }
            }else{
                $openid = $this->getUserOpenID($item['uid'],'min');
                if (!$openid) {
                    continue;
                }
                $this->send($openid,$item['tempCode'],$item['data'],'subscribe',$item['link'],$item['color']);
            }
        }
        event('wechat.subscribeTemplate',compact('res'));
    }

    /**
     * TODO
     * @param $uid
     * @return mixed
     * @author Qinii
     * @day 2020-06-29
     */
    public function getUserOpenID($uid,$type = 'wechat')
    {
        $user = app()->make(UserRepository::class)->get($uid);
        if (!$user) return null;
        $make = app()->make(WechatUserRepository::class);
        if($type == 'wechat') {
            return $make->idByOpenId((int)$user['wechat_user_id']);
        }else{
            return $make->idByRoutineId((int)$user['wechat_user_id']);
        }
    }


    /**
     * TODO
     * @param $openid
     * @param $tempCode
     * @param $data
     * @param $type
     * @param $link
     * @param $color
     * @return bool|mixed
     * @author Qinii
     * @day 2020-07-01
     */
    public function send($openid,$tempCode,$data,$type,$link,$color)
    {
        $template = new Template($type);
        $template->to($openid)->color($color);
        if ($link) $template->url($link);
        return $template->send($tempCode, $data);
    }

    /**
     * TODO 公众号
     * @param string $tempCode
     * @param $id
     * @return array|bool
     * @author Qinii
     * @day 2020-07-01
     */
    public  function templateMessage($params)
    {
        $data = [];
        $id = $params['id'];
        $tempId     = $params['tempId'];
        $params     = $params['params'] ?? [];
        $bill_make  = app()->make(UserBillRepository::class);
        $order_make = app()->make(StoreOrderRepository::class);
        $refund_make = app()->make(StoreRefundOrderRepository::class);
        $stie_url = rtrim(systemConfig('site_url'), '/');
        switch ($tempId)
        {
                //订单生成通知 2.1
            case 'ORDER_CREATE':
                /*
               {{first.DATA}}
               时间：{{keyword1.DATA}}
               商品名称：{{keyword2.DATA}}
               订单号：{{keyword3.DATA}}
               {{remark.DATA}}
                */
                $res = $order_make->selectWhere(['group_order_id' => $id]);
                if(!$res) return false;
                foreach ($res as $item){
                    $order = $order_make->getWith($item['order_id'],'orderProduct');
                    $data[] = [
                        'tempCode' => 'ORDER_CREATE',
                        'uid' => app()->make(StoreServiceRepository::class)->getNoticeServiceInfo($item->mer_id),
                        'data' => [
                            'first' => '您有新的生成订单请注意查看',
                            'keyword1' => $item->create_time,
                            'keyword2' => '「' . $order['orderProduct'][0]['cart_info']['product']['store_name'] . '」等',
                            'keyword3' => $item->order_sn,
                            'remark' => '查看详情'
                        ],
                        'link' =>  $stie_url. '/pages/admin/orderList/index?types=1&merId=' . $item->mer_id,
                        'color' => null
                    ];
                }
                break;
                //支付成功 2.1
            case 'ORDER_PAY_SUCCESS':
                /*
                {{first.DATA}}
                订单商品：{{keyword1.DATA}}
                订单编号：{{keyword2.DATA}}
                支付金额：{{keyword3.DATA}}
                支付时间：{{keyword4.DATA}}
                {{remark.DATA}}
                 */
                $group_order = app()->make(StoreGroupOrderRepository::class)->get($id);
                if(!$group_order) return false;
                $data[] = [
                    'tempCode' => 'ORDER_PAY_SUCCESS',
                    'uid' => $group_order->uid,
                    'data' => [
                        'first' => '您的订单已支付',
                        'keyword1' => $group_order->orderList[0]->orderProduct[0]['cart_info']['product']['store_name'],
                        'keyword2' => $group_order->group_order_sn,
                        'keyword3' => $group_order->pay_price,
                        'keyword4' => $group_order->pay_time,
                        'remark' => '我们会尽快发货，请耐心等待'
                    ],
                    'link' => $stie_url . '/pages/users/order_list/index?status=1',
                    'color' => null
                ];
                break;
                //管理员支付成功提醒 2.1
            case 'ADMIN_PAY_SUCCESS_CODE':
                /*
               {{first.DATA}}
               订单商品：{{keyword1.DATA}}
               订单编号：{{keyword2.DATA}}
               支付金额：{{keyword3.DATA}}
               支付时间：{{keyword4.DATA}}
               {{remark.DATA}}
                */
                $res = $order_make->selectWhere(['group_order_id' => $id]);
                if (!$res) return false;
                foreach ($res as $item) {
                    $data[] = [
                        'tempCode' => 'ADMIN_PAY_SUCCESS_CODE',
                        'uid' => app()->make(StoreServiceRepository::class)->getNoticeServiceInfo($item->mer_id),
                        'data' => [
                            'first' => '您有新的支付订单请注意查看。',
                            'keyword1' => mb_substr($item->orderProduct[0]->cart_info['product']['store_name'],0,15),
                            'keyword2' => $item->order_sn,
                            'keyword3' => $item->pay_price,
                            'keyword4' => $item->pay_time,
                            'remark' => '请尽快发货。'
                        ],
                        'link' => $stie_url . '/pages/admin/orderList/index?types=2&merId=' . $item->mer_id,
                        'color' => null
                    ];
                }
                break;
                //订单发货提醒 2.1
            case 'DELIVER_GOODS_CODE':
                /*
                {{first.DATA}}
                订单编号：{{keyword1.DATA}}
                物流公司：{{keyword2.DATA}}
                物流单号：{{keyword3.DATA}}
                {{remark.DATA}}
                */
                $res = $order_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'DELIVER_GOODS_CODE',
                    'uid' =>  $res->uid ,
                    'data' => [
                        'first' => '亲，宝贝已经启程了，好想快点来到你身边',
                        'keyword1' => $res['order_sn'],
                        'keyword2' => $res['delivery_name'],
                        'keyword3' => $res['delivery_id'],
                        'remark' => '请耐心等待收货哦。'
                    ],
                    'link' => $stie_url .'/pages/order_details/index?order_id='.$id,
                    'color' => null
                ];
                break;
                //订单配送提醒 2.1
            case 'ORDER_DELIVER_SUCCESS':
                /*
                {{first.DATA}}
                订单编号：{{keyword1.DATA}}
                订单金额：{{keyword2.DATA}}
                配送员：{{keyword3.DATA}}
                联系电话：{{keyword4.DATA}}
                {{remark.DATA}}
                */
                $res = $order_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'ORDER_DELIVER_SUCCESS',
                    'uid' =>  $res->uid ,
                    'data' => [
                        'first' => '亲，宝贝已经启程了，好想快点来到你身边',
                        'keyword1' => $res['order_sn'],
                        'keyword2' => $res['pay_prices'],
                        'keyword3' => $res['delivery_name'],
                        'keyword4' => $res['delivery_id'],
                        'remark' => '请耐心等待收货哦。'
                    ],
                    'link' => $stie_url .'/pages/order_details/index?order_id='.$id,
                    'color' => null
                ];
                break;
                //确认收货提醒 2.1
            case 'ORDER_TAKE_SUCCESS':
                /*
                {{first.DATA}}
                订单编号：{{keyword1.DATA}}
                订单金额：{{keyword2.DATA}}
                收货时间：{{keyword3.DATA}}
                {{remark.DATA}}
                 */
                $res = $order_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'ORDER_TAKE_SUCCESS',
                    'uid' => $res->uid,
                    'data' => [
                        'first' => '亲，宝贝已经签收',
                        'keyword1' => $res['order_sn'],
                        'keyword2' => $res['pay_price'],
                        'keyword3' => $res['orderStatus'][0]['change_time'],
                        'remark' => '请确认。'
                    ],
                    'link' => $stie_url .'/pages/order_details/index?order_id='.$id,
                    'color' => null
                ];
                break;
            case 'ADMIN_TAKE_DELIVERY_CODE':
                /*
               {{first.DATA}}
               订单编号：{{keyword1.DATA}}
               订单金额：{{keyword2.DATA}}
               收货时间：{{keyword3.DATA}}
               {{remark.DATA}}
                */
                $res = $order_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'ORDER_TAKE_SUCCESS',
                    'uid' => app()->make(StoreServiceRepository::class)->getNoticeServiceInfo($res->mer_id),
                    'data' => [
                        'first' => '亲，宝贝已经签收',
                        'keyword1' => $res['order_sn'],
                        'keyword2' => $res['pay_price'],
                        'keyword3' => $res['orderStatus'][0]['change_time'],
                        'remark' => '商品：【'.mb_substr($res['orderProduct'][0]['product']['store_name'],0,15). '】等'
                    ],
                    'link' => $stie_url .'/pages/order_details/index?order_id='.$id,
                    'color' => null
                ];
                break;
                //退款申请通知 2.1
            case 'ADMIN_RETURN_GOODS_CODE':
                /*
                {{first.DATA}}
                退款单号：{{keyword1.DATA}}
                退款原因：{{keyword2.DATA}}
                退款金额：{{keyword3.DATA}}
                申请时间：{{keyword4.DATA}}
                {{remark.DATA}}
                 */
                $res = $refund_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'ADMIN_RETURN_GOODS_CODE',
                    'uid' => app()->make(StoreServiceRepository::class)->getNoticeServiceInfo($res->mer_id),
                    'data' => [
                        'first' => '您有新的退款申请',
                        'keyword1' => $res['refund_order_sn'],
                        'keyword2' => $res['refund_message'],
                        'keyword3' => $res['refund_price'],
                        'keyword4' => $res['create_time'],
                        'remark' => '请及时处理'
                    ],
                    'link' => null,
                    'color' => null
                ];
                break;
                //商户同意退款 2.1
            case 'REFUND_SUCCESS_CODE':
                /*
                {{first.DATA}}
                退款申请单号：{{keyword1.DATA}}
                退款进度：{{keyword2.DATA}}
                时间：{{keyword3.DATA}}
                {{remark.DATA}}
                 */
                $res = $refund_make->get($id);
                if(!$res || $res['status'] != 1) return false;
                $data[] = [
                    'tempCode' => 'REFUND_SUCCESS_CODE',
                    'uid' => $res->uid,
                    'data' => [
                        'first' => '退款进度提醒',
                        'keyword1' => $res['refund_order_sn'],
                        'keyword2' => '商家已同意退货，请尽快将商品退回，并填写快递单号',
                        'keyword3' => $res->status_time,
                        'remark' => ''
                    ],
                    'link' => $stie_url .'/pages/users/refund/detail?id='.$id,
                    'color' => null
                ];
                break;
                //商户拒绝退款 2.1
            case 'REFUND_FAIL_CODE':
                /*
                {{first.DATA}}
                退款申请单号：{{keyword1.DATA}}
                退款进度：{{keyword2.DATA}}
                时间：{{keyword3.DATA}}
                {{remark.DATA}}
                 */
                $res = $refund_make->get($id);
                if(!$res || $res['status'] != -1) return false;
                $data[] = [
                    'tempCode' => 'REFUND_FAIL_CODE',
                    'uid' => $res->uid,
                    'data' => [
                        'first' => '退款进度提醒',
                        'keyword1' => $res['refund_order_sn'],
                        'keyword2' => '商家已拒绝退款，如有疑问请联系商户',
                        'keyword3' => $res->status_time,
                        'remark' => ''
                    ],
                    'link' => $stie_url.'/pages/users/refund/detail?id='.$id,
                    'color' => null
                ];
                break;
                //退款成功通知 2.1
            case 'REFUND_CONFORM_CODE':
                /*
                 {{first.DATA}}
                订单号：{{keyword1.DATA}}
                下单日期：{{keyword2.DATA}}
                商品名称：{{keyword3.DATA}}
                退款金额：{{keyword4.DATA}}
                退货原因：{{keyword5.DATA}}
                {{remark.DATA}}
                 */
                $res = $refund_make->get($id);
                if(!$res || $res['status'] != 3) return false;
                $data[] = [
                    'tempCode' => 'REFUND_CONFORM_CODE',
                    'uid' => $res->uid,
                    'data' => [
                        'first' => '亲，您有一个订单已退款',
                        'keyword1' => $res['refund_order_sn'],
                        'keyword2' => $res['order']['create_time'],
                        'keyword3' => '「'.mb_substr($res['refundProduct'][0]['product']['cart_info']['product']['store_name'],0,15).'」等',
                        'keyword4' => $res['refund_price'],
                        'keyword5' => $res['refund_message'],
                        'remark'   => '请查看详情'
                    ],
                    'link' => $stie_url.'/pages/users/refund/detail?id='.$id,
                    'color' => null
                ];
                break;
                //到货通知 2.1
            case 'PRODUCT_INCREASE':
                /*
                {{first.DATA}}
                商品名称：{{keyword1.DATA}}
                到货地点：{{keyword2.DATA}}
                到货时间：{{keyword3.DATA}}
                {{remark.DATA}
                 */
                $make = app()->make(ProductTakeRepository::class);
                $product = app()->make(ProductRepository::class)->getWhere(['product_id' => $id],'*',['attrValue']);
                if(!$product) return false;
                $unique[] = 1;
                foreach ($product['attrValue'] as $item) {
                    if($item['stock'] > 0){
                        $unique[] = $item['unique'];
                    }
                }
                $res = $make->getSearch(['product_id' => $id,'status' =>0,'type' => 2])->where('unique','in',$unique)->column('uid,product_id,product_take_id');
                $uids = array_unique(array_column($res,'uid'));
                if (!$uids) return false;
                $takeId = array_column($res,'product_take_id');
                foreach ($uids as $uid) {
                    $data[] = [
                        'tempCode' => 'PRODUCT_INCREASE',
                        'uid' => $uid,
                        'data' => [
                            'first' => '亲，你想要的商品已到货，可以购买啦~',
                            'keyword1' => '「'.$product->store_name.'」',
                            'keyword2' => $product->merchant->mer_name,
                            'keyword3' => date('Y-m-d H:i:s',time()),
                            'remark' => '到货商品：【'.mb_substr($product['store_name'],0,15).'】等，点击查看'
                        ],
                        'link' => $stie_url.'/pages/goods_details/index?id='.$id,
                        'color' => null
                    ];
                }
                $make->updates($takeId,['status' => 1]);
                break;
                //余额变动 2.1
            case 'USER_BALANCE_CHANGE':
                /*
                {{first.DATA}}
                用户名：{{keyword1.DATA}}
                变动时间：{{keyword2.DATA}}
                变动金额：{{keyword3.DATA}}
                可用余额：{{keyword4.DATA}}
                变动原因：{{keyword5.DATA}}
                {{remark.DATA}}
                 */
                $res = $bill_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'USER_BALANCE_CHANGE',
                    'uid' => $res->uid,
                    'data' => [
                        'first' => '资金变动提醒',
                        'keyword1' => $res->user->nickname,
                        'keyword2' => $res['create_time'],
                        'keyword3' => $res['number'],
                        'keyword4' => $res['balance'],
                        'keyword5' => $res['title'],
                        'remark' => '请确认'
                    ],
                    'link' => $stie_url.'/pages/users/user_money/index',
                    'color' => null
                ];
                break;
                //拼团成功 2.1
            case 'GROUP_BUYING_SUCCESS':
                /*
                {{first.DATA}}
                订单编号：{{keyword1.DATA}}
                订单信息：{{keyword2.DATA}}
                注意信息：{{keyword3.DATA}}
                {{remark.DATA}}
                 */
                $res = app()->make(ProductGroupBuyingRepository::class)->get($id);
                if(!$res) return false;
                $ret = app()->make(ProductGroupUserRepository::class)->getSearch(['group_buying_id' => $id])->where('uid','>',0)->select();
                foreach ($ret as $item){
                    $data[] = [
                        'tempCode' => 'GROUP_BUYING_SUCCESS',
                        'uid' => $item->uid,
                        'data' => [
                            'first' => '恭喜您拼团成功!',
                            'keyword1' => $item->orderInfo['order_sn'],
                            'keyword2' => '「'.mb_substr($res->productGroup->product['store_name'],0,15).'」',
                            'keyword3' => '无',
                            'remark' => ''
                        ],
                        'link' => $stie_url.'/pages/order_details/index?order_id='.$item['order_id'],
                        'color' => null
                    ];
                }
                break;
                //客服消息 2.1
            case'SERVER_NOTICE':
                /*
                {{first.DATA}}
                回复时间：{{keyword1.DATA}}
                回复内容：{{keyword2.DATA}}
                {{remark.DATA}}
                 */
                $first = '【平台】';
                if ($params['mer_id']) {
                    $mer = app()->make(MerchantRepository::class)->get($params['mer_id']);
                    $first = '【' . $mer['mer_name'] . '】';
                }
                $data[] = [
                    'tempCode' => 'SERVER_NOTICE',
                    'uid' => $id,
                    'data' => [
                        'first' =>  $first .'亲，您有新的消息请注意查看~',
                        'keyword1' => $params['keyword1'],
                        'keyword2' => $params['keyword2'],
                        'remark' => ''
                    ],
                    'link' => $stie_url.$params['url'],
                    'color' => null
                ];
                break;
            default:
                break;
        }
        return $data;
    }

    /**
     * TODO 小程序模板
     * @param string $tempCode
     * @param $id
     * @return array|bool
     * @author Qinii
     * @day 2020-07-01
     */
    public function subscribeTemplateMessage($params)
    {
        $data = [];
        $id = $params['id'];
        $tempId     = $params['tempId'];
        $user_make = app()->make(UserRechargeRepository::class);
        $order_make = app()->make(StoreOrderRepository::class);
        $refund_make = app()->make(StoreRefundOrderRepository::class);
        $order_group_make = app()->make(StoreGroupOrderRepository::class);
        $extract_make = app()->make(UserExtractRepository::class);
        switch($tempId)
        {
            //订单支付成功
            case 'ORDER_PAY_SUCCESS':
                $res = $order_group_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'ORDER_PAY_SUCCESS',
                    'uid' => $res->uid,
                    'data' => [
                        'character_string1' => $res->group_order_sn,
                        'amount2' => $res->pay_price,
                        'date3' => $res->pay_time,
                        'amount5' => $res->total_price,
                    ],
                    'link' => 'pages/users/order_list/index?status=1',
                    'color' => null
                ];
                break;
            //订单发货提醒(快递)
            case 'DELIVER_GOODS_CODE':
                $res = $order_make->getWith($id,'orderProduct');
                if(!$res) return false;
                $name = mb_substr($res['orderProduct'][0]['cart_info']['product']['store_name'],0,10);
                $data[] = [
                    'tempCode' => 'ORDER_POSTAGE_SUCCESS',
                    'uid' => $res->uid,
                    /**
                    快递单号{{character_string2.DATA}}
                    快递公司{{thing1.DATA}}
                    发货时间{{time3.DATA}}
                    订单商品{{thing5.DATA}}
                     */
                    'data' => [
                        'character_string2' => $res->delivery_id,
                        'thing1' => $res->delivery_name,
                        'time3' => date('Y-m-d H:i:s',time()),
                        'thing5' => '「'.$name.'」等',
                    ],

                    'link' => 'pages/order_details/index?order_id='.$id,
                    'color' => null
                ];
                break;
            //订单发货提醒(送货)
            case 'ORDER_DELIVER_SUCCESS':
                $res = $order_make->getWith($id,'orderProduct');
                if(!$res) return false;
                $name = mb_substr($res['orderProduct'][0]['cart_info']['product']['store_name'],0,10);
                $data[] = [
                    'tempCode' => 'ORDER_DELIVER_SUCCESS',
                    'uid' => $res->uid,
                    'data' => [
                        'thing8' => '「'.$name.'」等',
                        'character_string1' => $res->order_sn,
                        'name4' => $res->delivery_name,
                        'phone_number10' => $res->delivery_id,
                    ],
                    'link' => 'pages/order_details/index?order_id='.$id,
                    'color' => null
                ];
                break;
            //退款通知
            case 'REFUND_CONFORM_CODE':
                $res = $refund_make->get($id);
                if(!$res) return false;
                $name = mb_substr($res['refundProduct'][0]['product']['cart_info']['product']['store_name'],0,10);
                $data[] = [
                    'tempCode' => 'ORDER_REFUND_NOTICE',
                    'uid' => $res->uid,
                    'data' => [
                        'thing1' => '退款成功',
                        'thing2' => '「'.$name.'」等',
                        'character_string6' => $res->refund_order_sn,
                        'amount3' => $res->refund_price,
                        'thing13' => $res->fail_message ?? '',
                    ],
                    'link' => 'pages/users/refund/detail?id='.$id,
                    'color' => null
                ];
                break;
            //资金变动
            case 'USER_BALANCE_CHANGE':
                $res = $user_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'USER_BALANCE_CHANGE',
                    'uid' => $res->uid,
                    'data' => [
                        'character_string1' => $res->order_id,
                        'amount3' => $res->price,
                        'amount6' => $res->give_price,
                        'date5' => $res->pay_time,
                    ],
                    'link' => 'pages/users/user_money/index',
                    'color' => null
                ];
                break;
            //提现结果通知
            case 'EXTRACT_NOTICE':
                $res = $extract_make->get($id);
                if(!$res) return false;
                $data[] = [
                    'tempCode' => 'USER_EXTRACT',
                    'uid' => $res->uid,
                    'data' => [
                        'thing1' => $res->status == -1 ? '未通过' : '已通过',
                        'amount2' => empty($res->bank_code)?(empty($res->alipay_code)?$res->wechat:$res->alipay_code):$res->bank_code,
                        'thing3' => $res->give_price,
                        'date4' => $res->create_time,
                    ],
                    'link' => 'pages/users/user_spread_user/index',
                    'color' => null
                ];
                break;
            //到货通知
            case'PRODUCT_INCREASE':
                /*
                    商品名称 {{thing1.DATA}}
                    商品价格：{{amount5.DATA}}
                    温馨提示：{{thing2.DATA}}
                 */
                $make = app()->make(ProductTakeRepository::class);
                $product = app()->make(ProductRepository::class)->getWhere(['product_id' => $id],'*',['attrValue']);
                if(!$product) return false;
                $unique[] = 1;
                foreach ($product['attrValue'] as $item) {
                    if($item['stock'] > 0){
                        $unique[] = $item['unique'];
                    }
                }
                $res = $make->getSearch(['product_id' => $id,'status' =>0,'type' => 3])->where('unique','in',$unique)->column('uid,product_id,product_take_id');
                $uids = array_unique(array_column($res,'uid'));
                if (!$uids) return false;
                $takeId = array_column($res,'product_take_id');
                foreach ($uids as $uid) {
                    $data[] = [
                        'tempCode' => 'PRODUCT_INCREASE',
                        'uid' => $uid,
                        'data' => [
                            'thing1' => '「'.mb_substr($product->store_name,0,10) .'」',
                            'amount5' => $product->price,
                            'thing2' => '亲！你想要的商品已到货，可以购买啦~',
                        ],
                        'link' => 'pages/goods_details/index?id='.$id,
                        'color' => null
                    ];
                }
                if ($takeId) $make->updates($takeId,['status' => 1]);
                break;
            default:
                return false;
                break;
        }
       return $data;
    }
}
