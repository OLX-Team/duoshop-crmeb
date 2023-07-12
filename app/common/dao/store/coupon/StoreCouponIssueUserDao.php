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
use app\common\model\store\coupon\StoreCouponIssueUser;
use think\db\BaseQuery;

/**
 * Class StoreCouponIssueUserDao
 * @package app\common\dao\store\coupon
 * @author xaboy
 * @day 2020/6/2
 */
class StoreCouponIssueUserDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/6/2
     */
    protected function getModel(): string
    {
        return StoreCouponIssueUser::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/2
     */
    public function search(array $where)
    {
        return StoreCouponIssueUser::getDB()->when(isset($where['coupon_id']) && $where['coupon_id'] != '', function ($query) use ($where) {
            $query->where('coupon_id', $where['coupon_id']);
        })->when(isset($where['uid']) && $where['uid'] != '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->order('create_time');
    }
}
