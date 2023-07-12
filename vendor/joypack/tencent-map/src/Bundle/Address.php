<?php
namespace Joypack\Tencent\Map\Bundle;

use Joypack\Tencent\Map\Response;
use Joypack\Tencent\Map\Bundle;

/**
 * 地址解析（地址转坐标）
 * 本接口提供由地址描述到所述位置坐标的转换
 * 与逆地址解析的过程正好相反。
 */
class Address extends Bundle
{
    /**
     * 地址解析（地址转坐标）
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