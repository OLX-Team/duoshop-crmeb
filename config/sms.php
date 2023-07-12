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
// | 短信配置
// +----------------------------------------------------------------------

return [
    //默认支付模式
    'default' => 'yunxin',
    //单个手机每日发送上限
    'maxPhoneCount' => 10,
    //验证码每分钟发送上线
    'maxMinuteCount' => 20,
    //单个IP每日发送上限
    'maxIpCount' => 50,
    'stores' => [
        'yunxin' => [
            //短信模板id
            'template_id' => [
                //验证码
                'VERIFICATION_CODE' => 538393,
                //发货提醒 2.1
                'DELIVER_GOODS_CODE' => 520269,
                //确认收货提醒 2.1
                'ORDER_TAKE_SUCCESS' => 520271,
                //支付成功 2.1
                'ORDER_PAY_SUCCESS' => 520268,
                //改价提醒 2.1
                'PRICE_REVISION_CODE' => 528288,
                //提醒付款通知 -2.1
                'ORDER_PAY_FALSE' => 528116,
                //商家同意退款提醒 2.1
                'REFUND_SUCCESS_CODE' => 536113,
                //商家拒绝退款提醒 2.1
                'REFUND_FAIL_CODE' => 536112,
                //退款确认提醒 2.1
                'REFUND_CONFORM_CODE' => 536111,
                //管理员支付成功提醒 2.1
                'ADMIN_PAY_SUCCESS_CODE' => 520273,
                //管理员退款单提醒 -2.1
                'ADMIN_RETURN_GOODS_CODE' => 520274,
                //管理员确认收货 2.1
                'ADMIN_TAKE_DELIVERY_CODE' => 520422,
                //退货信息提醒 2.1
                'ADMIN_DELIVERY_CODE' => 440415,
                //直播通过通知 2.1
                'BROADCAST_ROOM_CODE' => 549311,
                //直播未通过通知 2.1
                'BROADCAST_ROOM_FAIL' => 549038,
                //预售订单尾款支付 2.1
                'PAY_PRESELL_CODE' => 543128,
                //商户申请入驻通过 2.1
                'APPLY_MER_SUCCESS' => 544837,
                //商户申请入驻未通过 2.1
                'APPLY_MER_FAIL' => 544838,
                //到货通知 2.1
                'PRODUCT_INCREASE' => 549146,
                //积分即将到期提醒 2.1
                'INTEGRAL_INVALID' => 550529,
                //商户申请分账通过 2.1
                'APPLYMENTS_SUCCESS' => 550526,
                //商户申请分账待验证 2.1
                'APPLYMENTS_SIGN' => 550525,
                //商户申请分账未通过 2.1
                'APPLYMENTS_FAIL' => 550523,
                //商户申请退回保证金通过 2.1
                'REFUND_MARGIN_SUCCESS' => 710327,
                //商户申请退回保证金未通过 2.1
                'REFUND_MARGIN_FAIL' => 710328,
                //付费会员充值成功提醒 2.1
                'SVIP_PAY_SUCCESS' => 856046
            ],
        ],
        //阿里云
        'aliyun' => [
            //短信模板id
            'template_id' => [
                //验证码
                'VERIFICATION_CODE' => '',
                //发货提醒
                'DELIVER_GOODS_CODE' => '',
                //确认收货提醒
                'TAKE_DELIVERY_CODE' => '',
                //支付成功
                'PAY_SUCCESS_CODE' => '',
                //改价提醒
                'PRICE_REVISION_CODE' => '',
                //订单未支付
                'ORDER_PAY_FALSE' => '',
                //商家同意退款提醒
                'REFUND_SUCCESS_CODE' => '',
                //商家拒绝退款提醒
                'REFUND_FAIL_CODE' => '',
                //退款确认提醒
                'REFUND_CONFORM_CODE' => '',
                //管理员支付成功提醒
                'ADMIN_PAY_SUCCESS_CODE' => '',
                //管理员退货提醒
                'ADMIN_RETURN_GOODS_CODE' => '',
                //管理员确认收货
                'ADMIN_TAKE_DELIVERY_CODE' => '',
                //退货信息提醒
                'ADMIN_DELIVERY_CODE' => '',
                //直播通过通知
                'BROADCAST_ROOM_CODE' => '',
                //直播未通过通知
                'BROADCAST_ROOM_FAIL' => '',
                //预售订单尾款支付
                'PAY_PRESELL_CODE' => '',
                //商户申请入驻通过
                'APPLY_MER_SUCCESS' => '',
                //商户申请入驻未通过
                'APPLY_MER_FAIL' => '',
                //到货通知
                'ARRIVAL_CODE' => '',
                //积分即将到期提醒
                'INTEGRAL_INVALID' => '',
                //商户申请分账通过
                'APPLYMENTS_SUCCESS' => '',
                //商户申请分账未通过
                'APPLYMENTS_FAIL' => '',
                //商户申请分账待验证
                'APPLYMENTS_SIGN' => '',
                //商户申请退回保证金通过
                'REFUND_MARGIN_SUCCESS' => '',
                //商户申请退回保证金未通过
                'REFUND_MARGIN_FAIL' => '',
            ],
        ]
    ],




];
