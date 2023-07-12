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


namespace app\common\model\store;


use app\common\model\BaseModel;

class CityArea extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'city_area';
    }

    public function parent()
    {
        return $this->hasOne(self::class,'id','parent_id');
    }

    public function getChildrenAttr()
    {
        return [];
    }

    public function getHasChildrenAttr()
    {
        $count = self::where('parent_id',$this->id)->count();
        return $count ? true : false;
    }
}
