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


namespace app\common\repositories\store\coupon;


use app\common\dao\store\coupon\StoreCouponProductDao;
use app\common\repositories\BaseRepository;

/**
 * Class StoreCouponProductRepository
 * @package app\common\repositories\store\coupon
 * @author xaboy
 * @day 2020/6/1
 * @mixin StoreCouponProductDao
 */
class StoreCouponProductRepository extends BaseRepository
{

    /**
     * StoreCouponProductRepository constructor.
     * @param StoreCouponProductDao $dao
     */
    public function __construct(StoreCouponProductDao $dao)
    {
        $this->dao = $dao;
    }

    public function productList($coupon_id, $page, $limit)
    {
        $query = $this->dao->search(compact('coupon_id'));
        $query->with(['product' => function ($query) {
            $query->field('product_id,store_name,image,price,stock,sales');
        }]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }
}
