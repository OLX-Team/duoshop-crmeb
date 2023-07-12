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

class StoreBrandCategory extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'store_brand_category_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'store_brand_category';
    }

    public function getAncestorsAttr($value)
    {
        $value = self::whereIn('store_brand_category_id',$this->path_ids)->order('level ASC')->column('cate_name');
        return implode('/',$value).'/'.$this->cate_name;
    }

    public function getPathIdsAttr()
    {
        return explode('/',$this->path);
    }

}
