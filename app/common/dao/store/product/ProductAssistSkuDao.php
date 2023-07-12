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
use app\common\model\store\product\ProductAssistSku;
use think\facade\Db;

class ProductAssistSkuDao extends BaseDao
{
    protected function getModel(): string
    {
        return ProductAssistSku::class;
    }

    public function clear($id)
    {
        $this->getModel()::getDB()->where('product_assist_id',$id)->delete();
    }

    public function descStock(int $product_assist_id, string $unique, int $desc)
    {
        return $this->getModel()::getDB()->where('product_assist_id', $product_assist_id)->where('unique', $unique)->update([
            'stock' => Db::raw('stock-' . $desc)
        ]);
    }

    public function incStock(int $product_assist_id, string $unique, int $desc)
    {
        return $this->getModel()::getDB()->where('product_assist_id', $product_assist_id)->where('unique', $unique)->update([
            'stock' => Db::raw('stock+' . $desc)
        ]);
    }
}

