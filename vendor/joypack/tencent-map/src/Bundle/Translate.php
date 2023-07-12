<?php
namespace Joypack\Tencent\Map\Bundle;

use Joypack\Tencent\Map\Response;
use Joypack\Tencent\Map\Bundle;

/**
 * 坐标转换
 * 实现从其它地图供应商坐标系或标准GPS坐标系
 * 批量转换到腾讯地图坐标系
 */
class Translate extends Bundle
{
    /**
     * 逆地址解析（坐标位置描述）
     * @param boolean $using_sig 使用签名方式校验
     * @return Response
     */
    public function request($using_sig=false)
    {
        $uri = '/ws/coord/v1/translate';
        
        
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