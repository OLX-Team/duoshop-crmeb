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


class GuaranteeTemplate extends BaseModel
{

    public static function tablePk(): string
    {
        return 'guarantee_template_id';
    }

    public static function tableName(): string
    {
        return 'guarantee_template';
    }


    public function templateValue()
    {
        return $this->hasMany(GuaranteeValue::class,'guarantee_template_id','guarantee_template_id');
    }

    public function getTemplateValueAttr()
    {
        return GuaranteeValue::haswhere('value',function($query){
            $query->where('status',1)->where('is_del',0);
        })
            ->where('guarantee_template_id',$this->guarantee_template_id)
            ->column('GuaranteeValue.guarantee_id');
    }

    public function searchGuaranteeTemplateIdAttr($query,$value)
    {
        $query->where('guarantee_template_id',$value);
    }

    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchKeywordAttr($query,$value)
    {
        $query->whereLike('template_name',"%{$value}%");
    }

    public function searchDateAttr($query,$value)
    {
        getModelTime($query,$value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchIsDelAttr($query,$value)
    {
        $query->where('is_del',$value);
    }
}
