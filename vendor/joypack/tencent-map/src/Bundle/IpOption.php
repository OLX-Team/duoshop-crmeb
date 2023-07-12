<?php
namespace Joypack\Tencent\Map\Bundle;

use Joypack\Tencent\Map\Option;

/**
 * IP定位
 * 参数
 */
class IpOption extends Option
{
    /**
     * IP地址
     * @param string $value
     */
    public function setIp($value)
    {
        $this->option['ip'] = $value;
    }
}