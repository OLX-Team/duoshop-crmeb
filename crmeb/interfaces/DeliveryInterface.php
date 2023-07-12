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


namespace crmeb\interfaces;


interface DeliveryInterface
{
    # public function __construct($config);
    public function addMerchant($data);     //注册商户
    public function addShop($data);         //创建门店
    public function updateShop($data);      //更新门店
    public function addOrder($data);        //发布订单
    public function getOrderPrice($data);   //计算订单价格
    public function getOrderDetail($data);  //获取订单详情
    public function cancelOrder($data);     //取消订单
    public function getRecharge($data);     //获取充值地址
    public function getBalance($data);      //获取余额
    public function addTip($data);          //支付小费
    public function getCity($data);         //获取城市信息
}
