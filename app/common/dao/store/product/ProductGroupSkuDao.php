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
namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\ProductGroupSku;

class ProductGroupSkuDao extends BaseDao
{
    public function getModel(): string
    {
        return ProductGroupSku::class;
    }

    public function clear($id)
    {
        return $this->getModel()::getDB()->where('product_group_id', $id)->delete();
    }

    public function incStock($product_group_id, $unique, $inc)
    {
        return ProductGroupSku::getDB()->where('product_group_id', $product_group_id)->where('unique', $unique)->inc('stock', $inc)->update();
    }

    public function descStock($product_group_id, $unique, $inc)
    {
        return ProductGroupSku::getDB()->where('product_group_id', $product_group_id)->where('unique', $unique)->dec('stock', $inc)->update();
    }

}
