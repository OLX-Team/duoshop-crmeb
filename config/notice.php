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

// +----------------------------------------------------------------------
// | 模板消息配置
// +----------------------------------------------------------------------

return [
    //短信模板id
    'sms' => [
        //验证码
        'VERIFICATION_CODE' => 538393,
        //发货提醒
        'DELIVER_GOODS_CODE' => 520269,
        //确认收货提醒
        'TAKE_DELIVERY_CODE' => 520271,
        //支付成功
        'PAY_SUCCESS_CODE' => 520268,
        //改价提醒
        'PRICE_REVISION_CODE' => 528288,
        //订单未支付
        'ORDER_PAY_FALSE' => 528116,
        //商家同意退款提醒
        'REFUND_SUCCESS_CODE' => 536113,
        //商家拒绝退款提醒
        'REFUND_FAIL_CODE' => 536112,
        //退款确认提醒
        'REFUND_CONFORM_CODE' => 536111,
        //管理员支付成功提醒
        'ADMIN_PAY_SUCCESS_CODE' => 520273,
        //管理员退货提醒
        'ADMIN_RETURN_GOODS_CODE' => 520274,
        //管理员确认收货
        'ADMIN_TAKE_DELIVERY_CODE' => 520422,
        //退货信息提醒
        'ADMIN_DELIVERY_CODE' => 536114,
        //直播通过通知
        'BROADCAST_ROOM_CODE' => 549311,
        //直播未通过通知
        'BROADCAST_ROOM_FAIL' => 549038,
        //预售订单尾款支付
        'PAY_PRESELL_CODE' => 543128,
        //商户申请入驻通过
        'APPLY_MER_SUCCESS' => 544837,
        //商户申请入驻未通过
        'APPLY_MER_FAIL' => 544838,
        //到货通知
        'PRODUCT_INCREASE' => 549146,
        //积分即将到期提醒
        'INTEGRAL_INVALID' => 550529,
        //商户申请分账通过
        'APPLYMENTS_SUCCESS' => 550526,
        //商户申请分账未通过
        'APPLYMENTS_FAIL' => 550523,
        //商户申请分账待验证
        'APPLYMENTS_SIGN' => 550525,
        //商户申请退回保证金通过
        'REFUND_MARGIN_SUCCESS' => 710327,
        //商户申请退回保证金未通过
        'REFUND_MARGIN_FAIL' => 710328,
    ],
    //微信
    'wechat' => [
        //订单生成通知
        'ORDER_CREATE' => 'OPENTM205213550',
        //支付成功
        'ORDER_PAY_SUCCESS' => 'OPENTM207791277',
        //订单发货提醒(快递)
        'ORDER_POSTAGE_SUCCESS' => 'OPENTM200565259',
        //订单发货提醒(送货)
        'ORDER_DELIVER_SUCCESS' => 'OPENTM207707249',
        //提现结果
        'EXTRACT_NOTICE' => 'OPENTM207601150',
        //订单收货通知
        'ORDER_TAKE_SUCCESS' => 'OPENTM413386489',
        //帐户资金变动提醒
        'USER_BALANCE_CHANGE' => 'OPENTM405847076',
        //退款申请通知
        'ORDER_REFUND_STATUS' => 'OPENTM407277862',
        //退款进度提醒
        'ORDER_REFUND_NOTICE' => 'OPENTM401479948',
        //退货确认提醒
        'ORDER_REFUND_END' => 'OPENTM406292353',
        //拼团成功
        'GROUP_BUYING_SUCCESS'=>'OPENTM417762951',
        //预订商品到货通知
        'PRODUCT_INCREASE' => 'OPENTM200443061',
        //访客消息通知
        'SERVER_NOTICE' => 'OPENTM417984821',
    ],
    //订阅消息
    'subscribe' => [
        //订单发货提醒(快递)
        'ORDER_POSTAGE_SUCCESS' => 1458,
        //提现成功通知
        'USER_EXTRACT' => 1470,
        //订单发货提醒(配送)
        'ORDER_DELIVER_SUCCESS' => 1128,
        //退款通知
        'ORDER_REFUND_NOTICE' => 1451,
        //充值成功
        'RECHARGE_SUCCESS' => 755,
        //订单支付成功
        'ORDER_PAY_SUCCESS' => 1927,
        //商品到货通知
        'PRODUCT_INCREASE' => 5019
    ],


];

