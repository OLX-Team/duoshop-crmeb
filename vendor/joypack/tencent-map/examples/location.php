<?php
use Joypack\Tencent\Map\Bundle\LocationOption;
use Joypack\Tencent\Map\Bundle\Location;

define('ROOT_PATH', dirname(__DIR__));

define('LOG_PATH', sprintf('%s/logs', ROOT_PATH));

spl_autoload_register(function ($className) {
    $className = str_replace('\\', '/', $className);
    $className = str_replace('Joypack/Tencent/Map/', '', $className);
    
    require_once sprintf('%s/src/%s.php', ROOT_PATH, $className);
});

$option = new LocationOption();
$option->setKey('<your app key>');
$option->setSecret('<your app secret>');

$option->setLocation('<lat>', '<lng>');

$location = new Location($option, LOG_PATH, true);

// 授权IP校验方式通信（无sig参数）
// $res = $address->request();

// 通过签名校验的方式通信
// 无需使用 $option->setSig()
$res = $location->request(true);

// $res->logger->print($res, true);

// 判断请求是否异常
if($res->error) {
    $res->logger->print($res->error, true);
}

// 打印接口返回的原始数据
// $res->logger->print($res->getOriginal(), true);

// 判断接口返回状态
if($res->status) {
    // 打印接口返回信息
    $res->logger->print($res->message, true);
}

// 打印接口返回数据（内部已完成Array解析）
$res->logger->print($res->result, true);
// 打印经纬度
$res->logger->print($res->result['location']['lng']);
