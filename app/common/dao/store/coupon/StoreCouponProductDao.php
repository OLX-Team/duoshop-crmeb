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


namespace app\common\dao\store\coupon;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\store\coupon\StoreCouponProduct;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class StoreCouponProductDao
 * @package app\common\dao\store\coupon
 * @author xaboy
 * @day 2020-05-13
 */
class StoreCouponProductDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return StoreCouponProduct::class;
    }

    /**
     * @param array $data
     * @return int
     * @author xaboy
     * @day 2020-05-13
     */
    public function insertAll(array $data)
    {
        return StoreCouponProduct::getDB()->insertAll($data);
    }

    /**
     * @param $couponId
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-05-13
     */
    public function clear($couponId)
    {
        return StoreCouponProduct::getDB()->where('coupon_id', $couponId)->delete();
    }

    /**
     * @param $productId
     * @return array
     * @author xaboy
     * @day 2020/6/1
     */
    public function productByCouponId($productId)
    {
        return StoreCouponProduct::getDB()->whereIn('product_id', $productId)->column('coupon_id');
    }

    public function search(array $where)
    {
        return StoreCouponProduct::getDB()
            ->when(isset($where['coupon_id']) && $where['coupon_id'] !== '', function ($query) use ($where) {
                return $query->where('coupon_id', $where['coupon_id']);
            })
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                return $query->where('type', $where['type']);
            });
    }
}
