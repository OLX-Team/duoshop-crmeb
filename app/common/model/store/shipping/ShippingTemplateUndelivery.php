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

namespace app\common\model\store\shipping;

use app\common\model\BaseModel;
use app\common\repositories\store\CityAreaRepository;

class ShippingTemplateUndelivery extends BaseModel
{
    /**
     * Author:Qinii
     * Date: 2020/5/6
     * Time: 14:20
     * @return string
     */
    public static function tablePk(): string
    {
        return 'shipping_template_undelivery_id';
    }


    /**
     * Author:Qinii
     * Date: 2020/5/6
     * Time: 14:20
     * @return string
     */
    public static function tableName(): string
    {
        return 'shipping_template_undelivery';
    }

    public function getCityIDsAttr($value, $data)
    {
        if (!$data['city_id']) return [];
        $city_id = explode('/', $data['city_id']);
        $data = app()->make(CityAreaRepository::class)->search([])->where('id', 'in', $city_id)->select();
        $result = [];
        foreach ($data as $v) {
            $result[] = array_map('intval', explode('/', trim($v['path'] . $v['id'], '/')));
        }
        return $result;
    }

    public function getCityNameAttr($value, $data)
    {
        if (!$data['city_id']) return [];
        $city_id = explode('/', trim($data['city_id'],'/'));
        $result = app()->make(CityAreaRepository::class)->search([])->where('id','in',$city_id)->column('id,name');
        return $result;
    }

    public function setCityIdAttr($value)
    {
        if ($value) return '/'.implode('/',$value).'/';
        return '';
    }
}
