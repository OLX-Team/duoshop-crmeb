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

namespace app\common\repositories\store\product;

use app\common\dao\store\product\ProductSkuDao;
use app\common\repositories\BaseRepository;
use think\exception\ValidateException;
use think\facade\Db;

class ProductSkuRepository extends BaseRepository
{
    public function __construct(ProductSkuDao $dao)
    {
        $this->dao = $dao;
    }

    const ACTIVE_TYPE_DISCOUNTS = 10;
    public function save(int $id, int $productId, array $data, $activeProductId = 0)
    {
        $storeProductServices = app()->make(ProductAttrValueRepository::class);
        foreach ($data as $item) {
            $skuData = $storeProductServices->search(['unique' => $item['unique']])->find();
            if (!$skuData) throw new ValidateException('属性规格不存在');
            $activeSku[] = [
                'active_id'     => $id,
                'active_product_id' => $activeProductId,
                'product_id'    => $productId,
                'active_type'   => self::ACTIVE_TYPE_DISCOUNTS,
                'price'         => $skuData['price'],
                'active_price'  => $item['active_price'] ?? $skuData['price'],
                'unique'        => $item['unique'],
            ];
        }
        $this->dao->insertAll($activeSku);
    }
}
