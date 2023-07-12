##### 腾讯位置服务

###### 地址解析（地址转坐标）
```
// 命名空间
use Joypack\Tencent\Map\WebService\AddressOption;
use Joypack\Tencent\Map\WebService\Address;

// 实例化参数
$option = new AddressOption();
// 设置接口 key
$option->setKey('<your app key>');
// 如果使用签名方式校验则需要配置 secret
$option->setSecret('<your app secret>');
// 设置要解析坐标的地址
$option->setAddress('安徽省合肥市瑶海区方庙街道万达金街');

// 将参数在这里传递
// 非开发模式只记录 error 类型的日志
$address = new Address($option, <日志存储路径>, <是否开发模式>);

// 授权IP校验方式通信（无sig参数）
// $res = $address->request();

// 通过签名校验的方式通信
// 无需使用 $option->setSig()
$res = $address->request(true);

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
```

###### 逆地址解析（坐标位置描述）
```
// 命名空间
use Joypack\Tencent\Map\WebService\LocationOption;
use Joypack\Tencent\Map\WebService\Location;

// 实例化参数
$option = new LocationOption();
// 设置接口 key
$option->setKey('<your app key>');
// 如果使用签名方式校验则需要配置 secret
$option->setSecret('<your app secret>');
// 设置要解析地址的经纬度坐标
$option->setLocation(31.877089, 117.347885);

// 将参数在这里传递
// 非开发模式只记录 error 类型的日志
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
```

###### 坐标转换
```
// 命名空间
use Joypack\Tencent\Map\WebService\TranslateOption;
use Joypack\Tencent\Map\WebService\Translate;

// 实例化参数
$option = new TranslateOption();
$option->setKey('<your app key>');
$option->setSecret('<your app secret>');
// 设置要转换的经纬度类型
$option->setType($option::TYPE_BAIDU);
// 设置经要转换的经纬度
$option->setLocation(31.877089, 117.347885);

// 
$location = new Translate($option, LOG_PATH, true);

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
$res->logger->print($res->locations, true);
```

###### IP定位
```
// 命名空间
use Joypack\Tencent\Map\WebService\IpOption;
use Joypack\Tencent\Map\WebService\Ip;

$option = new IpOption();
$option->setKey('<your app key>');
$option->setSecret('<your app secret>');
$option->setIp('202.106.0.20');

$location = new Ip($option, LOG_PATH, true);

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
```