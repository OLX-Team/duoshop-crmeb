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


namespace app\common\repositories\store\order;


use app\common\dao\store\order\StoreOrderProductDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\SpuRepository;

/**
 * Class StoreOrderProductRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/6/8
 * @mixin StoreOrderProductDao
 */
class StoreOrderProductRepository extends BaseRepository
{
    /**
     * StoreOrderProductRepository constructor.
     * @param StoreOrderProductDao $dao
     */
    public function __construct(StoreOrderProductDao $dao)
    {
        $this->dao = $dao;
    }

    public function getUserPayProduct(?string $keyword, int $uid, int $page, int $limit)
    {
        $query = $this->dao->getUserPayProduct($keyword, $uid)->group('product_id');
        $count = $query->count();
        $list = $query->setOption('field',[])->field('StoreOrderProduct.uid,StoreOrderProduct.product_id,StoreOrderProduct.product_type,spu_id,image,store_name,price')
            ->page($page, $limit)->select()->toArray();
       return compact('count', 'list');
    }

}
