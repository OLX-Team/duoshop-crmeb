<?php
namespace Joypack\Tencent\Map\Bundle;

use Joypack\Tencent\Map\Option;

/**
 * 地址解析（地址转坐标）
 * 参数
 */
class AddressOption extends Option
{
    /**
     * 地址
     * @param string $value
     */
    public function setAddress($value)
    {
        $this->option['address'] = $value;
    }
    
    /**
     * 指定地址所属城市
     * @param string $value
     */
    public function setRegion($value)
    {
        $this->option['region'] = $value;
    }
}