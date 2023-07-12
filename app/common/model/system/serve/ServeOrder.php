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
namespace app\common\model\system\serve;

use app\common\model\BaseModel;
use app\common\model\system\merchant\Merchant;
use app\common\model\user\User;

class ServeOrder extends BaseModel
{

    public static function tablePk(): string
    {
        return 'order_id';
    }

    public static function tableName(): string
    {
        return 'serve_order';
    }

    public function getOrderInfoAttr($value)
    {
        return json_decode($value);
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }

    public function userInfo()
    {
        return $this->hasOne(User::class,'mer_id','ud');
    }

    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    public function searchOrderSnAttr($query, $value)
    {
        $query->where('order_sn', $value);
    }

    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value);
    }

    public function searchMealIdAttr($query, $value)
    {
        $query->where('meal_id', $value);
    }

    public function searchMerIdAttr($query, $value)
    {
        $query->where('mer_id', $value);
    }

    public function searchDateAttr($query,$value)
    {
        getModelTime($query, $value);
    }

    public function searchPayTypeAttr($query,$value)
    {
        $query->where('value');
    }
}
