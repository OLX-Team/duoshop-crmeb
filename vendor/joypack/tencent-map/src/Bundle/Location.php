<?php
namespace Joypack\Tencent\Map\Bundle;

use Joypack\Tencent\Map\Response;
use Joypack\Tencent\Map\Bundle;

/**
 * 逆地址解析（坐标位置描述）
 * 本接口提供由坐标到坐标所在位置的文字描述的转换
 * 输入坐标返回地理位置信息和附近poi列表。
 */
class Location extends Bundle
{
    /**
     * 逆地址解析（坐标位置描述）
     * @param boolean $using_sig 使用签名方式校验
     * @return Response
     */
    public function request($using_sig=false)
    {
        $uri = '/ws/geocoder/v1';
        
        
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