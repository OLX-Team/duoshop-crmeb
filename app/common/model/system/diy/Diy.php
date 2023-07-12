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


namespace app\common\model\system\diy;

use app\common\model\BaseModel;

class Diy extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'diy';
    }

    public function searchTypeAttr($query,$value)
    {
        if (is_array($value)) {
            $query->whereIn('type',$value);
        } else {
            $query->where('type',$value);
        }
    }

    public function searchMerIdAttr($query, $value)
    {
        if ($value) {
            $query->where(function ($query) use ($value){
                $query->where('mer_id', $value)->whereOr('is_default',2);
            });
        } else {
            $query->where('mer_id', $value)->where('is_default','<',2);
        }
    }

    public function searchIsDefaultAttr($query, $value)
    {
        $query->where('is_default', $value);
    }
}
