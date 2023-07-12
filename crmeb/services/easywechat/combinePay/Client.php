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


namespace crmeb\services\easywechat\combinePay;


use app\common\model\store\order\StoreRefundOrder;
use crmeb\services\easywechat\BaseClient;
use think\exception\ValidateException;
use think\facade\Route;
use function EasyWeChat\Payment\generate_sign;

class Client extends BaseClient
{

    public function handleNotify($callback)
    {
        $request = request();
        $success = $request->post('event_type') === 'TRANSACTION.SUCCESS';
        $data = $this->decrypt($request->post('resource', []));

        $handleResult = call_user_func_array($callback, [json_decode($data, true), $success]);
        if (is_bool($handleResult) && $handleResult) {
            $response = [
                'code' => 'SUCCESS',
                'message' => 'OK',
            ];
        } else {
            $response = [
                'code' => 'FAIL',
                'message' => $handleResult,
            ];
        }

        return response($response, 200, [], 'json');
    }

    public function pay($type, array $order)
    {
        $params = [
            'combine_out_trade_no' => $order['order_sn'],
            'combine_mchid' => $this->app['config']['service_payment']['merchant_id'],
            'combine_appid' => $this->app['config']['app_id'],
            'scene_info' => [
                'device_id' => 'shop system',
                'payer_client_ip' => request()->ip(),
            ],
            'sub_orders' => [],
            'notify_url' => rtrim(systemConfig('site_url'), '/') . Route::buildUrl($this->app['config']['service_payment']['type'] . 'CombinePayNotify', ['type' => $order['attach']])->build(),
        ];

        if ($type === 'h5') {
            $params['scene_info']['h5_info'] = [
                'type' => $order['h5_type'] ?? 'Wap'
            ];
        }

        foreach ($order['sub_orders'] as $sub_order) {
            $subOrder = [
                'mchid' => $this->app['config']['service_payment']['merchant_id'],
                'amount' => [
                    'total_amount' => intval($sub_order['pay_price'] * 100),
                    'currency' => 'CNY',
                ],
                'settle_info' => [
                    'profit_sharing' => true
                ],
                'out_trade_no' => $sub_order['order_sn'],
                'sub_mchid' => $sub_order['sub_mchid']
            ];
            $subOrder['attach'] = $sub_order['attach'] ?? $order['attach'] ?? '';
            $subOrder['description'] = $sub_order['body'] ?? $order['body'] ?? '';
            $params['sub_orders'][] = $subOrder;
        }

        if (isset($order['openid'])) {
            $params['combine_payer_info'] = [
                'openid' => $order['openid'],
            ];
        }
        $content = json_encode($params, JSON_UNESCAPED_UNICODE);

        $res = $this->request('/v3/combine-transactions/' . $type, 'POST', ['sign_body' => $content]);
        if (isset($res['code'])) {
            throw new ValidateException('微信接口报错:' . $res['message']);
        }
        return $res;
    }

    public function payApp(array $options)
    {
        $res = $this->pay('app', $options);
        return $this->configForAppPayment($res['prepay_id']);
    }

    /**
     * @param string $type 场景类型，枚举值： iOS：IOS移动应用； Android：安卓移动应用； Wap：WAP网站应用
     */
    public function payH5(array $options, $type = 'Wap')
    {
        $options['h5_type'] = $type;
        return $this->pay('h5', $options);
    }

    public function payJs($openId, array $options)
    {
        $options['openid'] = $openId;
        $res = $this->pay('jsapi', $options);
        return $this->configForJSSDKPayment($res['prepay_id']);
    }

    public function payNative(array $options)
    {
        return $this->pay('native', $options);
    }

    public function profitsharingOrder(array $options, bool $finish = false)
    {
        $params = [
            'appid' => $this->app['config']['app_id'],
            'sub_mchid' => $options['sub_mchid'],
            'transaction_id' => $options['transaction_id'],
            'out_order_no' => $options['out_order_no'],
            'receivers' => [],
            'finish' => $finish
        ];

        foreach ($options['receivers'] as $receiver) {
            $data = [
                'amount' => intval($receiver['amount'] * 100),
                'description' => $receiver['body'] ?? $options['body'] ?? '',
            ];
            $data['receiver_account'] = $receiver['receiver_account'];
            if (isset($receiver['receiver_name'])) {
                $data['receiver_name'] = $receiver['receiver_name'];
                $data['type'] = 'PERSONAL_OPENID';
            } else {
                $data['type'] = 'MERCHANT_ID';
            }
            $params['receivers'][] = $data;
        }
        $content = json_encode($params);
        $res = $this->request('/v3/ecommerce/profitsharing/orders', 'POST', ['sign_body' => $content]);
        if (isset($res['code'])) {
            throw new ValidateException('微信接口报错:' . $res['message']);
        }
        return $res;
    }

    public function profitsharingFinishOrder(array $params)
    {
        $content = json_encode($params);
        $res = $this->request('/v3/ecommerce/profitsharing/finish-order', 'POST', ['sign_body' => $content]);
        if (isset($res['code'])) {
            throw new ValidateException('微信接口报错:' . $res['message']);
        }
        return $res;
    }

    public function payOrderRefund(string $order_sn, array $options)
    {
        $params = [
            'sub_mchid' => $options['sub_mchid'],
            'sp_appid' => $this->app['config']['app_id'],
            'out_trade_no' => $options['order_sn'],
            'out_refund_no' => $options['refund_order_sn'],
            'amount' => [
                'refund' => intval($options['refund_price'] * 100),
                'total' => intval($options['pay_price'] * 100),
                'currency' => 'CNY'
            ]
        ];
        if (isset($options['reason'])) {
            $params['reason'] = $options['reason'];
        }
        if (isset($options['refund_account'])) {
            $params['refund_account'] = $options['refund_account'];
        }
        $content = json_encode($params);
        $res = $this->request('/v3/ecommerce/refunds/apply', 'POST', ['sign_body' => $content], true);
        if (isset($res['code'])) {
            throw new ValidateException('微信接口报错:' . $res['message']);
        }
        return $res;
    }

    public function returnAdvance($refund_id, $sub_mchid)
    {
        $res = $this->request('/v3/ecommerce/refunds/' . $refund_id . '/return-advance', 'POST', ['sign_body' => json_encode(compact('sub_mchid'))], true);
        if (isset($res['code'])) {
            throw new ValidateException('微信接口报错:' . $res['message']);
        }
        return $res;
    }

    public function configForPayment($prepayId, $json = true)
    {
        $params = [
            'appId' => $this->app['config']['app_id'],
            'timeStamp' => strval(time()),
            'nonceStr' => uniqid(),
            'package' => "prepay_id=$prepayId",
            'signType' => 'RSA',
        ];
        $message = $params['appId'] . "\n" .
            $params['timeStamp'] . "\n" .
            $params['nonceStr'] . "\n" .
            $params['package'] . "\n";
        openssl_sign($message, $raw_sign, $this->getPrivateKey(), 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);

        $params['paySign'] = $sign;

        return $json ? json_encode($params) : $params;
    }

    /**
     * Generate app payment parameters.
     *
     * @param string $prepayId
     *
     * @return array
     */
    public function configForAppPayment($prepayId)
    {
        $params = [
            'appid' => $this->app['config']['app_id'],
            'partnerid' => $this->app['config']['service_payment']['merchant_id'],
            'prepayid' => $prepayId,
            'noncestr' => uniqid(),
            'timestamp' => time(),
            'package' => 'Sign=WXPay',
        ];
        $message = $params['appid'] . "\n" .
            $params['timestamp'] . "\n" .
            $params['noncestr'] . "\n" .
            $params['prepayid'] . "\n";
        openssl_sign($message, $raw_sign, $this->getPrivateKey(), 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);

        $params['sign'] = $sign;

        return $params;
    }

    public function configForJSSDKPayment($prepayId)
    {
        $config = $this->configForPayment($prepayId, false);

        $config['timestamp'] = $config['timeStamp'];
        unset($config['timeStamp']);

        return $config;
    }

}
