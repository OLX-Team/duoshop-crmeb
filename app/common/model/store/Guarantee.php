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


class Guarantee extends BaseModel
{

    public static function tablePk(): string
    {
        return 'guarantee_id';
    }

    public static function tableName(): string
    {
        return 'guarantee';
    }

    public function searchGuaranteeIdAttr($query,$value)
    {
        $query->where('guarantee_id',$value);
    }

    public function searchGuaranteeNameAttr($query,$value)
    {
        $query->where('guarantee_name',$value);
    }

    public function searchGuaranteeInfoAttr($query,$value)
    {
        $query->where('guarantee_Info',$value);
    }

    public function searchKeywordAttr($query,$value)
    {
        $query->whereLike('guarantee_name',"%{$value}%");
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
