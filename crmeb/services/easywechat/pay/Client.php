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


namespace crmeb\services\easywechat\pay;


use crmeb\services\easywechat\BaseClient;
use think\exception\ValidateException;
use think\facade\Log;

class Client extends BaseClient
{

    protected $isService = false;

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

    public function pay($type, $order)
    {

        $params = [
            'appid' => $this->app['config']['app_id'],
            'mchid' => $this->app['config']['payment']['merchant_id'],
            'description' => $order['body'],
            'out_trade_no' => $order['out_trade_no'],
            'attach' => $order['attach'],
            'notify_url' => $this->app['config']['payment']['notify_url'],
            'amount' => [
                'total' => intval($order['total_fee']),
                'currency' => 'CNY'
            ],
            'scene_info' => [
                'device_id' => 'shop system',
                'payer_client_ip' => request()->ip(),
            ],
        ];

        if ($type === 'h5') {
            $params['scene_info']['h5_info'] = [
                'type' => $order['h5_type'] ?? 'Wap'
            ];
        }

        if (isset($order['openid'])) {
            $params['payer'] = [
                'openid' => $order['openid']
            ];
        }
        Log::info('微信v3支付：'.var_export($params,true));
        $content = json_encode($params, JSON_UNESCAPED_UNICODE);

        $res = $this->request('/v3/pay/transactions/' . $type, 'POST', ['sign_body' => $content]);
        if (isset($res['code'])) {
            throw new ValidateException('微信接口报错:' . $res['message']);
        }
        return $res;
    }

    public function payApp($options)
    {
        $res = $this->pay('app', $options);
        return $this->configForAppPayment($res['prepay_id']);
    }

    /**
     * @param string $type 场景类型，枚举值： iOS：IOS移动应用； Android：安卓移动应用； Wap：WAP网站应用
     */
    public function payH5($options, $type = 'Wap')
    {
        $options['h5_type'] = $type;
        return $this->pay('h5', $options);
    }

    public function payJsapi($options)
    {
        $res = $this->pay('jsapi', $options);
        return $this->configForJSSDKPayment($res['prepay_id']);
    }

    public function payNative($options)
    {
        unset($options['openid']);
        return $this->pay('native', $options);
    }

    public function refund($orderNo, $refundNo, $totalFee, $refundFee, $opUserId = null, $type, $refundAccount, $refundReason)
    {
        $params = [
            $type => $orderNo,
            'out_refund_no' => $refundNo,
            'amount' => [
                'refund' => intval($refundFee),
                'total' => intval($totalFee),
                'currency' => 'CNY'
            ]
        ];

        if (isset($refundReason)) {
            $params['reason'] = $refundReason;
        }
//        if (isset($refundAccount)) {
//            $params['refund_account'] = $refundAccount;
//        }
        $content = json_encode($params);
        $res = $this->request('/v3/refund/domestic/refunds', 'POST', ['sign_body' => $content], true);
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
            'partnerid' => $this->app['config']['payment']['merchant_id'],
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
