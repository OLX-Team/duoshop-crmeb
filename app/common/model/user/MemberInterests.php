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


namespace app\common\model\user;


use app\common\model\BaseModel;

class MemberInterests extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'interests_id';
    }

    public static function tableName(): string
    {
        return 'member_interests';
    }

    public function searchTypeAttr($query, $value)
    {
        $query->where('type',$value);
    }

    public function searchInterestsIdAttr($query, $value)
    {
        $query->where('interests_id',$value);
    }

    public function searchNameAttr($query, $value)
    {
        $query->whereLike('name',"%{$value}%");
    }

    public function searchBrokerageLevelAttr($query, $value)
    {
        $query->where('brokerage_level',$value);
    }

    public function searchLevelAttr($query, $value)
    {
        $query->where('brokerage_level', '<=', $value);
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

}
