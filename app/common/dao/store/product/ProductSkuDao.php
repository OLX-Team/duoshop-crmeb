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
use app\common\model\store\product\ProductSku;
use think\facade\Db;

class ProductSkuDao extends BaseDao
{
    protected function getModel(): string
    {
        return ProductSku::class;
    }

    public function clear(int $id, int $type)
    {
        $this->getModel()::getDB()->where('active_id', $id)->where('active_type', $type)->delete();
    }

    public function descStock(int $active_id, string $unique, int $desc)
    {
        return $this->getModel()::getDB()->where('active_id', $active_id)->where('unique', $unique)->update([
            'stock' => Db::raw('stock-' . $desc)
        ]);
    }

    public function incStock(int $active_id, string $unique, int $desc)
    {
        return $this->getModel()::getDB()->where('active_id', $active_id)->where('unique', $unique)->update([
            'stock' => Db::raw('stock+' . $desc)
        ]);
    }
}

