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

namespace app\common\model\system\merchant;

use app\common\model\BaseModel;

class MerchantApplyments extends BaseModel
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 6/22/21
     */
    public static function tablePk(): string
    {
        return 'mer_applyments_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 6/22/21
     */
    public static function tableName(): string
    {
        return 'merchant_applyments';
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }

    public function searchOutRequestNoAttr($query,$value)
    {
        $query->where('out_request_no',$value);
    }

    public function searchKeywordAttr($query,$value)
    {
        $query->whereLike('mer_name',"%{$value}%");
    }

    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }

    public function searchDateAttr($query,$value)
    {
        getModelTime($query,$value);
    }

    public function searchMerApplymentsIdAttr($query,$value)
    {
        $query->where('mer_applyments_id',$value);
    }
}
