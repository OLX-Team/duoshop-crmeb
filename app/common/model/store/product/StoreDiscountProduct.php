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
use app\common\repositories\store\product\ProductSkuRepository;

class StoreDiscountProduct extends BaseModel
{

    public static function tablePk(): string
    {
        return 'discount_product_id';
    }

    public static function tableName(): string
    {
        return 'store_discounts_product';
    }

    public function product()
    {
        return $this->hasOne(Product::class,'product_id', 'product_id');
    }

    public function productSku()
    {
        return $this->hasMany(ProductSku::class,'active_product_id', 'discount_product_id')->where('active_type',ProductSkuRepository::ACTIVE_TYPE_DISCOUNTS);
    }

    public function searchProductIdAttr($query, $value)
    {
        $query->where('product_id', $value);
    }
}
