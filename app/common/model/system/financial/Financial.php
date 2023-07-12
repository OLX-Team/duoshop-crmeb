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


namespace app\common\model\system\financial;


use app\common\model\BaseModel;
use app\common\model\system\admin\Admin;
use app\common\model\system\merchant\Merchant;
use app\common\model\system\merchant\MerchantAdmin;
use think\facade\Db;

class Financial extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'financial_id';
    }

    public static function tableName(): string
    {
        return 'financial';
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }

    public function getFinancialAccountAttr($value)
    {
        return json_decode($value);
    }

    public function getImageAttr($value)
    {
        return explode(',',$value);
    }

    public function getAdminIdAttr($value)
    {
        return Admin::where('admin_id',$value)->value('real_name');
    }

    public function getMerAdminIdAttr($value)
    {
        return MerchantAdmin::where('merchant_admin_id',$value)->value('real_name');
    }

    public function searchFinancialIdAttr($query,$value)
    {
        $query->where('financial_id',$value);
    }
    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }
    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }
    public function searchFinancailStatusAttr($query,$value)
    {
        $query->where('financial_status',$value);
    }
    public function searchFinancailTypeAttr($query,$value)
    {
        $query->where('financial_type',$value);
    }
    public function searchKeywordsAttr($query,$value)
    {
        $query->whereLike('keywords',"%{$value}%");
    }
    public function searchDateAttr($query,$value)
    {
        getModelTime($query,$value);
    }
    public function searchIsDelAttr($query,$value)
    {
        $query->where('is_del',$value);
    }

}
