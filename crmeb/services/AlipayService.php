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


use crmeb\services\alipay\AlipayNotify;
use Payment\Client;
use Payment\Proxies\AlipayProxy;
use think\exception\ValidateException;
use think\facade\Route;

class AlipayService
{
    /**
     * @var Client
     */
    protected $application;

    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->application = new Client(Client::ALIPAY, $config);
    }

    public static function create($type = '')
    {
        return new self(self::getConfig($type));
    }

    public static function getConfig($type = '')
    {
        $config = systemConfig(['site_url', 'alipay_app_id', 'alipay_public_key', 'alipay_private_key', 'alipay_open']);
        if (!$config['alipay_open']) throw new ValidateException('支付宝支付未开启');
        $siteUrl = $config['site_url'];
        return [
            'app_id' => $config['alipay_app_id'],
            'sign_type' => 'RSA2', // RSA  RSA2
            'limit_pay' => [
//                'balance',// 余额
//                'moneyFund',// 余额宝
//                'debitCardExpress',// 	借记卡快捷
                //'creditCard',//信用卡
                //'creditCardExpress',// 信用卡快捷
                //'creditCardCartoon',//信用卡卡通
                //'credit_group',// 信用支付类型（包含信用卡卡通、信用卡快捷、花呗、花呗分期）
            ], // 用户不可用指定渠道支付当有多个渠道时用“,”分隔

            // 支付宝公钥字符串
            'ali_public_key' => $config['alipay_public_key'],
            // 自己生成的密钥字符串
            'rsa_private_key' => $config['alipay_private_key'],
            'notify_url' => rtrim($siteUrl, '/') . Route::buildUrl('alipayNotify', ['type' => $type])->build(),
            'return_url' => $siteUrl,
        ];
    }

    public function qrPaymentPrepare($out_trade_no, $total_fee, $body, $detail = '')
    {
        $data = [
            'body' => $detail ?: $body,
            'subject' => $body,
            'trade_no' => $out_trade_no,
            'amount' => floatval($total_fee),
            'time_expire' => time() + (15 * 60),
            'return_params' => $out_trade_no,
        ];
        try {
            $res = $this->application->pay(Client::ALI_CHANNEL_QR, $data);
        } catch (\Exception $e) {
            throw new ValidateException('支付宝支付错误返回：' . $e->getMessage());
        }
        return $res['qr_code'];
    }

    public function appPaymentPrepare($out_trade_no, $total_fee, $body, $detail = '')
    {
        $data = [
            'body' => $detail ?: $body,
            'subject' => $body,
            'trade_no' => $out_trade_no,
            'amount' => floatval($total_fee),
            'time_expire' => time() + (15 * 60),
            'goods_type' => 1,
            'return_params' => $out_trade_no,
        ];
        try {
            $res = $this->application->pay(Client::ALI_CHANNEL_APP, $data);
        } catch (\Exception $e) {
            throw new ValidateException('支付宝支付错误返回：' . $e->getMessage());
        }
        return $res;
    }

    public function wapPaymentPrepare($out_trade_no, $total_fee, $body, $return_url = '', $detail = '')
    {
        $data = [
            'body' => $detail ?: $body,
            'subject' => $body,
            'trade_no' => $out_trade_no,
            'amount' => floatval($total_fee),
            'time_expire' => time() + (15 * 60),
            'goods_type' => 1,
            'return_params' => $out_trade_no,
        ];
        $config = AlipayProxy::$config;
        if ($return_url)
            $config->offsetSet('return_url', $return_url);
        $data['quit_url'] = $config->get('return_url');
        try {
            $res = $this->application->pay(Client::ALI_CHANNEL_WAP, $data);
        } catch (\Exception $e) {
            throw new ValidateException('支付宝支付错误返回：' . $e->getMessage());
        }
        return $res;
    }

    public function payOrderRefund($trade_sn, array $data)
    {
        $data = [
            'trade_no' => $trade_sn,
            'refund_fee' => floatval($data['refund_price']),
            'reason' => $data['refund_id'],
            'refund_no' => $data['refund_id'],
        ];
        return $this->application->refund($data);
    }

    public function notify($type)
    {
        $this->application->notify(new AlipayNotify($type));
    }
}
