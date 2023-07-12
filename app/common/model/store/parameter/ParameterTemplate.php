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

namespace app\common\model\store\parameter;

use app\common\model\BaseModel;
use app\common\model\system\merchant\Merchant;
use app\common\model\system\Relevance;
use app\common\repositories\system\RelevanceRepository;

class ParameterTemplate extends BaseModel
{


    public static function tablePk(): string
    {
        return 'template_id';
    }

    public static function tableName(): string
    {
        return 'parameter_template';
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }

    public function parameter()
    {
        return $this->hasMany(Parameter::class,'template_id','template_id');
    }

    public function cateId()
    {
        return $this->hasMany(Relevance::class,'left_id','template_id')->where('type', RelevanceRepository::PRODUCT_PARAMES_CATE);
    }

    public function searchCateIdAttr($query, $value)
    {
        $id = Relevance::where('right_id',$value)->where('type', RelevanceRepository::PRODUCT_PARAMES_CATE)->column('left_id');
        $query->where('template_id','in',$id);
    }

    public function searchTemplateNameAttr($query, $value)
    {
        $query->whereLike('template_name',"%{$value}%");
    }

    public function searchTemplateIdsAttr($query, $value)
    {
        $query->whereIn('template_id',$value);
    }

    public function searchMerIdAttr($query, $value)
    {
        $query->where('mer_id',$value);
    }

    public function searchMerNameAttr($query, $value)
    {
        $value = Merchant::whereLike('mer_name',"%{$value}%")->coupon('mer_id');
        $query->whereIn('mer_id',$value);
    }

    public function searchIsMerAttr($query, $value)
    {
        if ($value == 1) {
            $query->where('mer_id','>',0);
        } else {
            $query->where('mer_id',0);
        }
    }

}
