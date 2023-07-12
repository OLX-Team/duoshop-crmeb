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
namespace crmeb\services\delivery\storage;

use crmeb\basic\BaseStorage;
use crmeb\interfaces\DeliveryInterface;
use think\exception\ValidateException;

class Uupt extends BaseStorage implements DeliveryInterface
{
    //uu跑腿
    public $config;

    //域名
    // const BASE_URL = 'http://openapi.test.uupt.com/';
    const BASE_URL = 'https://openapi.uupt.com';

    //发布订单
    const ADD_ORDER = '/v2_0/addorder.ashx';

    //计算价格
    const GET_ORDER_PRICE = '/v2_0/getorderprice.ashx';

    //详情
    const GET_ORDER_DETAIL = '/v2_0/getorderdetail.ashx';

    //充值
    const GET_RECHARGE = '/v2_0/getrecharge.ashx';

    //取消
    const CANCEL_ORDER = '/v2_0/cancelorder.ashx';

    //查询门店
    const GET_SHOP = '/v2_0/getshoplist.ashx';

    //余额
    const GET_BALANCEDE = '/v2_0/getbalancedetail.ashx';

    //获取城市
    const GET_CITY = '/v2_0/getcitylist.ashx';

    public function initialize(array $config)
    {
        $this->config = $config;
    }

    //发布订单
    public function addOrder($data)
    {
        $params = [
            'price_token' => $data['price_token'],
            'order_price' => $data['total_money'],
            'balance_paymoney' => $data['need_paymoney'],
            'receiver' => $data['receiver'],
            'receiver_phone' => $data['receiver_phone'],
            'callback_url' => $data['callback_url'],
            'push_type' => '0', //推送方式（0 开放订单，2测试订单）默认传0即可
            'special_type' => '0',
            'callme_withtake' => $data['callme_withtake'] ?? '0',
            'pay_type' => 0,
        ];
        if ($data['note']) $params['note'] = $data['note'];
        return $this->sendRequest(self::ADD_ORDER, $params);
    }

    //计算订单价格
    public function getOrderPrice($data)
    {
        $params = [
            'from_address' => $data['from_address'],
            'to_address'   => $data['to_address'],
            'city_name'    => $data['city_name'],
            'goods_type'   => $data['goods_type'],
            'send_type'    =>'0',
            'to_lat'       => (string)$data['to_lat'],
            'to_lng'       => (string)$data['to_lng'],
            'from_lat'     => (string)$data['from_lat'],
            'from_lng'     => (string)$data['from_lng'],
            'goods_weight' => $data['cargo_weight'],
        ];
        return $this->sendRequest(self::GET_ORDER_PRICE, $params);
    }

    //获取订单详情
    public function getOrderDetail($data)
    {
        if($data['order_sn']) $params['origin_id'] = $data['order_sn'];
        if($data['order_code']) $params['order_code'] = $data['order_code'];
        return $this->sendRequest(self::GET_ORDER_DETAIL, $params);
    }

    //取消订单
    public function cancelOrder($data)
    {
        $params = [
            'origin_id' => $data['origin_id'],
            'order_code'=> $data['order_code'],
            'reason'    => $data['reason'],
        ];
        return $this->sendRequest(self::CANCEL_ORDER, $params);
    }

    //获取充值地址
    public function getRecharge($data)
    {
        return $this->sendRequest(self::GET_RECHARGE, $data);
    }

    //获取余额
    public function getBalance($data)
    {
        $res = $this->sendRequest(self::GET_BALANCEDE, $data);
        return [
            'deliverBalance' => $res['AccountMoney']
        ];
    }
    //支付小费
    public function addTip($data)
    {
    }
    //获取城市信息
    public function getCity($data = [])
    {
        $res = $this->sendRequest(self::GET_CITY, $data);
        foreach ($res['CityList'] as $item) {
            $data[] = [
                'key' => $item['CityName'],
                'label' => $item['CityName'],
            ];
        }
        return $data;
    }


    public function sendRequest($api, $params = [])
    {
        $url = self::BASE_URL . $api;
        $params = $this->bulidRequestParams($params);
        $response = $this->httpRequestWithPost($url, $params);
        $data = $this->getMessage($response);
        return $data;
    }

    /**
     * 构造请求数据
     * data:业务参数
     */
    public function bulidRequestParams(array $params = [])
    {
        $params['openid'] = $this->config['open_id'];
        $params['appid'] = $this->config['app_id'];
        $params['nonce_str'] = str_replace('-', '', $this->guid());
        $params['timestamp'] = time();
        $params['sign'] = $this->_sign($params);
        $arr = [];
        foreach ($params as $key => $value) {
            $arr[] = $key . '=' . $value;
        }
        $curlPost = implode('&', $arr);
        return $curlPost;
    }

    // 生成guid
    public function guid()
    {
        mt_srand((float)microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return strtolower(str_replace('-', '', $uuid));
    }

    /**
     * 签名生成sign
     */
    public function _sign($data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if (!is_null($value)) {
                $str .=  $key . '=' . $value . '&';
            }
        }
        $str  .= 'key=' . $this->config['app_key'];
        $str = mb_strtoupper($str, 'UTF-8');
        return strtoupper(md5($str));
    }

    /**
     * 发送请求,POST
     * @param $url 指定URL完整路径地址
     * @param $data 请求的数据
     */
    public function httpRequestWithPost($url, $data)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        $resp = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        return $resp;
    }

    protected function getMessage($json, $message = '未知错误！')
    {
        $data = json_decode($json, true);
        if (!in_array($data['return_code'], ['ok', 'fail'])) {
            isset($data['return_msg']) && $message = $data['return_msg'];
            $mes = $message == '未知错误！' ? $this->getCodeMap($data['return_code']) : $message;
            throw new ValidateException('【UU错误提示】:'.$mes);
        } else {
            return $data;
        }
    }


    /**
     * 获取错误代码
     * @param  string $key 代码
     * @return String 错误代码与信息
     */
    protected function getCodeMap($key)
    {
        $codeMap = [
            '-101' => '参数格式校验错误',
            '-102' => 'timestamp错误',
            '-103' => 'appid无效',
            '-104' => '签名校验失败',
            '-105' => 'openid无效',
            '-199' => '参数格式校验错误',
            '-1001' => '无法解析起始地',
            '-1002' => '无法解析目的地',
            '-1003' => '无法获取订单城市相关信息',
            '-1004' => '订单小类出现错误',
            '-1005' => '没有用户信息',
            '-1006' => '优惠券ID错误',
            '-2001' => 'price_token无效',
            '-2002' => 'price_token无效',
            '-2003' => '收货人电话格式错误',
            '-2004' => 'special_type错误',
            '-2005' => 'callme_withtake错误',
            '-2006' => 'order_price错误',
            '-2007' => 'balance_paymoney错误',
            '-2008' => '订单总金额错误',
            '-2009' => '支付金额错误',
            '-2010' => '用户不一致',
            '-2011' => '手机号错误',
            '-2012' => '不存在绑定关系',
            '-4001' => '取消原因不能为空',
            '-4002' => '订单编号无效',
            '-5001' => '订单编号无效',
            '-5002' => '订单编号无效',
            '-5003' => '订单编号无效',
            '-10001' => '发送频率过快，请稍候重试',
            '-11001' => '请输入正确的验证码',
        ];
        $info = isset($codeMap[$key]) ? $codeMap[$key] : false;

        return $info;
    }

    //创建商户
    public function addMerchant($data)
    {
        return true;
    }

    //创建门店
    public function addShop($data)
    {
        return true;
    }

    //更新门店
    public function updateShop($data)
    {
        return true;
    }

    public function getBusiness()
    {
        return [
            ['key' => 1, 'label' => '美食'],
            ['key' => 2, 'label' => '鲜花'],
            ['key' => 3, 'label' => '蛋糕'],
            ['key' => 4, 'label' => '手机'],
            ['key' => 5, 'label' => '钥匙'],
            ['key' => 6, 'label' => '文件'],
            ['key' => 0, 'label' => '其他'],
        ];
    }
}
