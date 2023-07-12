<?php

namespace crmeb\services\easywechat\miniPayment;

use EasyWeChat\Core\AccessToken;
use EasyWeChat\Payment\Merchant;
use \crmeb\services\easywechat\BaseClient;

class Client extends BaseClient
{
    private $expire_time = 7000;


    /**
     * 创建订单 支付
     */
    const API_SET_CREATE_ORDER = 'https://api.weixin.qq.com/shop/pay/createorder';
    /**
     * 退款
     */
    const API_SET_REFUND_ORDER = 'https://api.weixin.qq.com/shop/pay/refundorder';


    /**
     * Merchant instance.
     *
     * @var \EasyWeChat\Payment\Merchant
     */
    protected $merchant;

    /**
     * ProgramSubscribeService constructor.
     * @param AccessToken $accessToken
     */
    public function __construct(AccessToken $accessToken, Merchant $merchant)
    {
        parent::__construct($accessToken);
        $this->merchant = $merchant;
    }

    /**
     * 支付
     * @param array $params [
     *                      'openid'=>'支付者的openid',
     *                      'out_trade_no'=>'商家合单支付总交易单号',
     *                      'total_fee'=>'支付金额',
     *                      'wx_out_trade_no'=>'商家交易单号',
     *                      'body'=>'商品描述',
     *                      'attach'=>'支付类型',  //product 产品  member 会员
     *                      ]
     * @param $isContract
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createorder($order)
    {
        $params = [
            'openid' => $order['openid'],    // 支付者的openid
            'combine_trade_no' => $order['out_trade_no'],  // 商家合单支付总交易单号
            'expire_time' => time() + $this->expire_time,
            'sub_orders' => [
                [
                    'mchid' => $this->merchant->merchant_id,
                    'amount' => (int)$order['total_fee'],
                    'trade_no' => $order['out_trade_no'],
                    'description' => $order['body']
                ]
            ]
        ];
        return $this->parseJSON('post', [self::API_SET_CREATE_ORDER, json_encode($params)]);
    }

    /**
     * 退款
     * @param array $params [
     *                      'openid'=>'退款者的openid',
     *                      'trade_no'=>'商家交易单号',
     *                      'transaction_id'=>'支付单号',
     *                      'refund_no'=>'商家退款单号',
     *                      'total_amount'=>'订单总金额',
     *                      'refund_amount'=>'退款金额',  //product 产品  member 会员
     *                      ]
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refund($orderNo, $refundNo, $totalFee, $refundFee,$openId,$transactionId)
    {
        $params = [
            'openid' => $openId,
            'mchid' => $this->merchant->merchant_id,
            'trade_no' => $orderNo,
            'transaction_id' => $transactionId,
            'refund_no' => $refundNo,
            'total_amount' => $totalFee,
            'refund_amount' => $refundFee,
        ];
        return $this->parseJSON('post', [self::API_SET_REFUND_ORDER, json_encode($params)]);
    }


}
