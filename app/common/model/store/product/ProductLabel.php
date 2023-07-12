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
namespace app\common\model\store\product;

use app\common\model\BaseModel;

class ProductLabel extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 8/17/21
     */
    public static function tablePk(): string
    {
        return 'product_label_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 8/17/21
     */
    public static function tableName(): string
    {
        return 'store_product_label';
    }

    public function searchMerIdAttr($query, $value)
    {
        $query->where('mer_id', $value);
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    public function searchNameAttr($query, $value)
    {
        $query->whereLike('name', "%{$value}%");
    }

    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value);
    }
}
