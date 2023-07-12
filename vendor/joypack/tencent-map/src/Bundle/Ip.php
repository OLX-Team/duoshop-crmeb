<?php
namespace Joypack\Tencent\Map\Bundle;

use Joypack\Tencent\Map\Response;
use Joypack\Tencent\Map\Bundle;

/**
 * IP定位
 * 通过终端设备IP地址获取其当前所在地理位置
 * 精确到市级，常用于显示当地城市天气预报、初始化用户城市等非精确定位场景。
 */
class Ip extends Bundle
{
    /**
     * IP定位
     * @param boolean $using_sig 使用签名方式校验
     * @return Response
     */
    public function request($using_sig=false)
    {
        $uri = '/ws/location/v1/ip';
        
        
        if($using_sig) {
            $this->option->setSig($uri);
        }
        
        $data = $this->option->getAll();
        
        //$this->request->logger->print($data, true);
        
        $this->request->uri($uri);
        $this->request->query($data);
        
        return $this->request->get();
    }
}