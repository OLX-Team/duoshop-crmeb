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
use app\common\model\store\product\Spu;
use app\common\model\system\Relevance;
use app\common\repositories\system\RelevanceRepository;

class StoreActivity extends BaseModel
{

    public static function tablePk(): string
    {
        return 'activity_id';
    }

    public static function tableName(): string
    {
        return 'store_activity';
    }

    public function getFullAttr($val)
    {
        return $val ? (float)$val : $val;
    }

    public function spu()
    {
        return $this->hasMany(Spu::class, 'activity_id', 'activity_id');
    }

    public function socpeData()
    {
        return $this->hasMany(Relevance::class,'left_id','activity_id')->whereIn('type',[RelevanceRepository::SCOPE_TYPE_STORE,RelevanceRepository::SCOPE_TYPE_CATEGORY,RelevanceRepository::SCOPE_TYPE_PRODUCT]);
    }

    public function searchIsShowAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_show', $value);
        }
    }

    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status',$value);
        }
    }

    public function searchActivityTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('activity_type', $value);
        }
    }

    public function searchIsStatusAttr($query, $value)
    {
        $query->whereIn('status',[0,1]);
    }

    public function searchActivityIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('activity_id', $value);
        }
    }

    public function searchDateAttr($query, $value)
    {
        if ($value !== '') {
            getModelTime($query, $value, 'create_time');
        }
    }

    public function searchKeywordAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('activity_id|activity_name', '%' . $value . '%');
        }
    }

    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_del', $value);
        }
    }

    public function searchGtEndTimeAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereTime('end_time','>', $value);
        }
    }

}
