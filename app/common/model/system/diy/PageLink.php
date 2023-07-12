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

class PageLink extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'page_link';
    }

    public function category()
    {
        return $this->hasOne(PageCategory::class,'id', 'cate_id');
    }

    public function searchIsMerAttr($query, $value)
    {
        $query->where('is_mer', $value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('status',$value);
    }


}
