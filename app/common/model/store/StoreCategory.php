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
use app\common\model\store\product\Product;
use think\Model;

class StoreCategory extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'store_category_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'store_category';
    }

    /**
     * 获取父级名称
     * @Author:Qinii
     * @Date: 2020/5/22
     * @param $value
     * @return string
     */
    public function getAncestorsAttr($value)
    {
        $value = self::whereIn('store_category_id',$this->path_ids)->order('level ASC')->column('cate_name');
        return implode('/',$value).'/'.$this->cate_name;
    }

    /**
     * 获取path的id
     * @Author:Qinii
     * @Date: 2020/5/22
     * @return array
     */
    public function getPathIdsAttr()
    {
        return explode('/',$this->path);
    }
    public function getHasProductAttr()
    {
        return Product::where('cate_id',$this->store_category_id)->count() > 0 ? 1 : 0;
    }


    /**
     * 获取子集id
     * @Author:Qinii
     * @Date: 2020/5/22
     * @param $value
     * @return array
     */
    public function getChildIdsAttr($value)
    {
        return self::where('path','like','%/'.$this->store_category_id.'/%')->column('store_category_id');
    }

    public function searchIdAttr($query,$value)
    {
        $query->where('store_category_id',$value);
    }

    public function searchIdsAttr($query,$value)
    {
        $query->whereIn('store_category_id',$value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('is_show',$value);
    }

    public function searchMerIdsAttr($query,$value)
    {
        $query->whereIn('mer_id',$value);
    }

}
