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
use app\common\model\store\coupon\StoreCouponSend;

class StoreCouponSendDao extends BaseDao
{

    protected function getModel(): string
    {
        return StoreCouponSend::class;
    }

    public function search(array $where)
    {
        return StoreCouponSend::getDB()->alias('A')->leftJoin('StoreCoupon B', 'B.coupon_id = A.coupon_id')
            ->when(isset($where['coupon_name']) && $where['coupon_name'] !== '', function ($query) use ($where) {
                $query->whereLike('B.title', "%{$where['coupon_name']}%");
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'A.create_time');
            })
            ->when(isset($where['coupon_type']) && $where['coupon_type'] !== '', function ($query) use ($where) {
                $query->where('B.type', $where['coupon_type']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('A.status', $where['status']);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('A.mer_id', $where['mer_id']);
            });
    }
}
