<?php
namespace Joypack\Tencent\Map\Bundle;

use Joypack\Tencent\Map\Option;

/**
 * 坐标转换
 * 参数
 */
class TranslateOption extends Option
{
    const TYPE_GPS = 1;
    const TYPE_SOGOU = 2;
    const TYPE_BAIDU = 3;
    const TYPE_MAPBAR = 4;
    const TYPE_DEFAULT = 5;
    const TYPE_SOGOU_MERCATOR = 6;
    
    /**
     * 预转换的坐标
     * @param string $value
     */
    public function setLocation($lat, $lng)
    {
        $this->option['locations'] = "{$lat},{$lng}";
    }
    
    /**
     * 预转换的坐标，支持批量转换
     * @param array $locations
     * <p>['lat,lng', [lat,lng]]</p>
     */
    public function setLocations(array $locations)
    {
        $pieces = [];
        foreach ($locations as $item) {
            if(is_array($item)) {
                $pieces[] = "{$item[0]},{$item[1]}";
            }
        }
        $this->option['locations'] = implode(';', $pieces);
    }
    
    /**
     * 设置坐标类型
     * @param number $value
     *  1 GPS坐标
     *  2 sogou经纬度
     *  3 baidu经纬度
     *  4 mapbar经纬度
     *  5 腾讯、google、高德坐标[默认]
     *  6 sogou墨卡托
     */
    public function setType($value=self::TYPE_DEFAULT)
    {
        $this->option['type'] = $value;
    }
}