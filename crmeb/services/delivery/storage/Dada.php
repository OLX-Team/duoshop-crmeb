<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------\
namespace crmeb\services\delivery\storage;

use crmeb\basic\BaseStorage;
use crmeb\interfaces\DeliveryInterface;
use think\exception\ValidateException;

class Dada extends BaseStorage implements DeliveryInterface
{
     const BASE_URL = 'https://newopen.imdada.cn';

    const ADD_MERCHANT = '/merchantApi/merchant/add';

    const ADD_SHOP = '/api/shop/add';

    const UPDATE_SHOP = '/api/shop/update';

    const GET_CITY_CODE = '/api/cityCode/list';

    const GET_SHOP_DETAIL = '/api/shop/detail';

    const GET_ORDER_PRICE = '/api/order/queryDeliverFee';

    const ADD_ORDER_AFTER_QUERY = '/api/order/addAfterQuery';

    const ADD_ORDER_STSATUS_QUERY = '/api/order/status/query';

    const GET_REASONS = '/api/order/cancel/reasons';

    const CANCEL_ORDER = '/api/order/formalCancel';

    const GET_BALANCE = '/api/balance/query';

    const GET_RECHARGE = '/api/recharge';

    public $config;

    public function initialize(array $config)
    {
        $this->config = $config;
    }

    //创建商户
    public function addMerchant($data)
    {
        return $this->sendRequest(self::ADD_MERCHANT, $data);
    }

    //创建门店
    public function addShop($data)
    {
        $parmas = [];
        foreach ($data as $key => $item) {
            if (!($item['lng']) || !($item['lat']))
                throw new ValidateException('经纬度不能为空');
            if (!($item['phone']) || !($item['contact_name']))
                throw new ValidateException('联系人信息不能为空');
            if (!($item['business']))
                throw new ValidateException('配送物品分类不能为空');
            if (!($item['station_name']) || !$item['station_address'])
                throw new ValidateException('门店信息不能为空');
            $value = [
                'lng' => (float)$item['lng'],
                'lat' => (float)$item['lat'],
                'phone' => $item['phone'],
                'business' => (int)$item['business'],
                'contact_name' => $item['contact_name'],
                'station_name' => $item['station_name'],
                'station_address' => $item['station_address'],
                'status' => 1,
                'origin_shop_id' => $item['origin_shop_id'],
            ];
            if (isset($item['username']) && $item['username']) $value['username'] = $item['username'];
            if (isset($item['password']) && $item['password']) $value['password'] = $item['password'];
            $parmas[] = $value;
        }
        return $this->sendRequest(self::ADD_SHOP, $parmas);
    }

    //更新门店
    public function updateShop($data)
    {
        $params['origin_shop_id'] = $data['origin_shop_id'];
        if (isset($data['new_shop_id'])) $params['new_shop_id'] = $data['new_shop_id'];
        if (isset($data['station_name'])) $params['station_name'] = $data['station_name'];
        if (isset($data['business'])) $params['business'] = $data['business'];
        if (isset($data['station_address'])) $params['station_address'] = $data['station_address'];
        if (isset($data['lng'])) $params['lng'] = $data['lng'];
        if (isset($data['lat'])) $params['lat'] = $data['lat'];
        if (isset($data['contact_name'])) $params['contact_name'] = $data['contact_name'];
        if (isset($data['phone'])) $params['phone'] = $data['phone'];
        if (isset($data['status'])) $params['status'] = $data['status'];
        return $this->sendRequest(self::UPDATE_SHOP, $params);
    }

    //预发布订单
    public function addOrder($data)
    {
        $params = [
            'deliveryNo' => $data['deliveryNo'],
        ];
        return $this->sendRequest(self::ADD_ORDER_AFTER_QUERY, $params);
    }

    //计算订单价格
    public function getOrderPrice($data)
    {
        $params = [
            'shop_no'         => $data['shop_no'],
            'origin_id'       => $data['origin_id'],
            'city_code'       => $data['city_code'],
            'cargo_price'     => $data['cargo_price'],
            'is_prepay'       => $data['is_prepay'],
            'receiver_name'   => $data['receiver_name'],
            'receiver_address'=> $data['receiver_address'],
            'callback'        => $data['callback_url'],
            'cargo_weight'    => $data['cargo_weight'],
            'receiver_phone'  => $data['receiver_phone'],
            'is_finish_code_needed'=> $data['is_finish_code_needed'],
        ];
        return $this->sendRequest(self::GET_ORDER_PRICE, $params);
    }

    //获取订单详情
    public function getOrderDetail($data)
    {
        $params['order_id'] = $data['origin_id'];
        return $this->sendRequest(self::ADD_ORDER_STSATUS_QUERY, $params);
    }
    //取消订单
    public function cancelOrder($data)
    {
        $params['order_id'] = $data['origin_id'];
        $params['cancel_reason'] = $data['cancel_reason'] ?? '无';
        $params['cancel_reason_id'] = $data['reason'];
        return $this->sendRequest(self::CANCEL_ORDER, $params);
    }
    //获取充值地址
    public function getRecharge($data =[])
    {
        $params = [
            'amount' => $data['amount'] ?? 100,
            'category'=> $data['category'] ?? 'PC',
        ];
        return $this->sendRequest(self::GET_RECHARGE, $params);
    }
    //获取余额
    public function getBalance($data)
    {
        $params['category'] = $data['category'] ?? 3;
        $res = $this->sendRequest(self::GET_BALANCE, $params);
        return [
            'deliverBalance' => $res['deliverBalance']
        ];
    }
    //支付小费
    public function addTip($data)
    {
    }
    //取消原因
    public function reasons($data = '')
    {
        $options = $this->sendRequest(self::GET_REASONS, $data);
        foreach ($options as $option) {
            $value = $option['id'];
            $label = $option['reason'];
            $res[] = compact('value','label');
        }
        return $res;
    }
    //获取城市信息
    public function getCity($data = '')
    {
        $res = $this->sendRequest(self::GET_CITY_CODE, $data);
        foreach ($res as $item) {
            $data[] = [
                'key' => $item['cityName'],
                'label' => $item['cityName'],
            ];
        }
        return $data;
    }

    public function getShopDetail($id)
    {
        $data = ['origin_shop_id' => $id];
        return $this->sendRequest(self::GET_SHOP_DETAIL, $data);
    }

    public function getBusiness()
    {
        return [
            ['key' => 1  , 'label' => '食品小吃'],
            ['key' => 2  , 'label' => '饮料'],
            ['key' => 3  , 'label' => '鲜花绿植'],
            ['key' => 5  , 'label' => '其他'],
            ['key' => 8  , 'label' => '文印票务'],
            ['key' => 9  , 'label' => '便利店'],
            ['key' => 13 , 'label'  => '水果生鲜'],
            ['key' => 19 , 'label'  => '同城电商'],
            ['key' => 20 , 'label'  => '医药'],
            ['key' => 21 , 'label'  => '蛋糕'],
            ['key' => 24 , 'label'  => '酒品'],
            ['key' => 25 , 'label'  => '小商品市场'],
            ['key' => 26 , 'label'  => '服装'],
            ['key' => 27 , 'label'  => '汽修零配'],
            ['key' => 28 , 'label'  => '数码家电'],
            ['key' => 29 , 'label'  => '小龙虾/烧烤'],
            ['key' => 31 , 'label'  => '超市'],
            ['key' => 51 , 'label'  => '火锅'],
            ['key' => 53 , 'label'  => '个护美妆'],
            ['key' => 55 , 'label'  => '母婴'],
            ['key' => 57 , 'label'  => '家居家纺'],
            ['key' => 59 , 'label'  => '手机'],
            ['key' => 61 , 'label'  => '家装'],
            ['key' => 63 , 'label'  => '成人用品'],
        ];
    }

    public function sendRequest($api, $params)
    {
        $url = self::BASE_URL . $api;
        $params = $this->bulidRequestParams($params);
        $response = $this->httpRequestWithPost($url, $params);
        $data = $this->getMessage($response);
        return $data;
    }

    /**
     * 构造请求数据
     * data:业务参数，json字符串
     */
    public function bulidRequestParams($params)
    {
        $requestParams = array();
        $requestParams['app_key'] = $this->config['app_key'];
        $requestParams['body'] = json_encode($params);
        $requestParams['format'] = 'json';
        $requestParams['v'] = '1.0';
        $requestParams['source_id'] = $this->config['source_id'];
        $requestParams['timestamp'] = time();
        $requestParams['signature'] = $this->_sign($requestParams);
        return json_encode($requestParams);
    }

    /**
     * 签名生成signature
     */
    public function _sign($data)
    {
        //1.升序排序
        ksort($data);
        //2.字符串拼接
        $args = "";
        foreach ($data as $key => $value) {
            $args .= $key . $value;
        }
        $args = $this->config['app_secret'] . $args . $this->config['app_secret'];
        //3.MD5签名,转为大写
        $sign = strtoupper(md5($args));
        return $sign;
    }

    /**
     * 发送请求,POST
     * @param $url 指定URL完整路径地址
     * @param $data 请求的数据
     */
    public function httpRequestWithPost($url, $data, $headers = [])
    {
        $headers = array(
            'Content-Type: application/json',
        );
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if (isset($info['http_code']) && $info['http_code'] == 200) {
            return $resp;
        }
        return ;
    }

    protected function getMessage($json, $message = '未知错误！')
    {
        $data = json_decode($json, true);
        if ($data['code'] !== 0) {
            isset($data['msg']) && $message = $data['msg'];
            if ($data['errorCode'] == 7718) {
                foreach ($data['result']['failedList'] as $datum) {
                    $message .= ':'.$datum['shopName'].'/'. $datum['msg'].';';
                }
            }
            throw new ValidateException('【达达错误提示】:'.$message);
        } else {
            if ($data['status'] == 'success') return $data['result'] ?? $data;
            return $data;

        }
    }


}
