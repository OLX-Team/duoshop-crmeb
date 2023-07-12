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
use app\common\model\store\coupon\StoreCouponUser;

/**
 * Class StoreCouponUserDao
 * @package app\common\dao\store\coupon
 * @author xaboy
 * @day 2020-05-14
 */
class StoreCouponUserDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return StoreCouponUser::class;
    }

    public function search(array $where)
    {
        return StoreCouponUser::when(isset($where['username']) && $where['username'] !== '', function ($query) use ($where) {
            $query->hasWhere('user', [['nickname', 'LIKE', "%{$where['username']}%"]]);
        })->when(isset($where['coupon_type']) && $where['coupon_type'] !== '', function ($query) use ($where) {
            $query->hasWhere('coupon', ['type' => $where['coupon_type']]);
        })->alias('StoreCouponUser')->when(isset($where['coupon']) && $where['coupon'] !== '', function ($query) use ($where) {
            $query->whereLike('StoreCouponUser.coupon_title', "%{$where['coupon']}%");
        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('StoreCouponUser.status', $where['status']);
        })->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('StoreCouponUser.uid', $where['uid']);
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('StoreCouponUser.mer_id', $where['mer_id']);
        })->when(isset($where['coupon_id']) && $where['coupon_id'] !== '', function ($query) use ($where) {
            $query->where('StoreCouponUser.coupon_id', $where['coupon_id']);
        })->when(isset($where['coupon']) && $where['coupon'] !== '', function ($query) use ($where) {
            $query->whereLike('StoreCouponUser.coupon_title|StoreCouponUser.coupon_id', "%{$where['coupon']}%");
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('StoreCouponUser.type', $where['type']);
        })->when(isset($where['send_id']) && $where['send_id'] !== '', function ($query) use ($where) {
            $query->where('StoreCouponUser.send_id', $where['send_id'])->where('StoreCouponUser.type', 'send');
        })->when(isset($where['is_mer']) && $where['is_mer'] !== '', function ($query) use ($where) {
            $query->where('StoreCouponUser.mer_id', '<>',0);
        })->when(isset($where['statusTag']) && $where['statusTag'] !== '', function ($query) use ($where) {
            if ($where['statusTag'] == 1) {
                $query->where('StoreCouponUser.status', 0);
            } else {
                $query->whereIn('StoreCouponUser.status', [1, 2])->where('StoreCouponUser.create_time', '>', date('Y-m-d H:i:s', strtotime('-60 day')));
            }
        })->order('StoreCouponUser.coupon_user_id DESC');
    }

    public function validIntersection($merId, $uid, array $ids): array
    {
        $time = date('Y-m-d H:i:s');
        return StoreCouponUser::getDB()->whereIn('coupon_user_id', $ids)->where('start_time', '<', $time)->where('end_time', '>', $time)
            ->where('is_fail', 0)->where('status', 0)->where('mer_id', $merId)->where('uid', $uid)->column('coupon_user_id');
    }

    public function validQuery($type)
    {
        $time = date('Y-m-d H:i:s');
        return StoreCouponUser::getDB()
            ->when($type, function ($query) use($time){
                $query->where('start_time', '<', $time);
            })
            ->where('end_time', '>', $time)->where('is_fail', 0)->where('status', 0);
    }

    public function failCoupon()
    {
        $time = date('Y-m-d H:i:s');
        return StoreCouponUser::getDB()->where('end_time', '<', $time)->where('is_fail', 0)->where('status', 0)->update(['status' => 2]);
    }

    public function userTotal($uid, $type = 1)
    {
        return $this->validQuery($type)->where('uid', $uid)->count();
    }

    public function usedNum($couponId)
    {
        return StoreCouponUser::getDB()->where('coupon_id', $couponId)->where('status', 1)->count();
    }

    public function sendNum($couponId, $sendId = null, $status = null)
    {
        return StoreCouponUser::getDB()->where('coupon_id', $couponId)->when($sendId, function ($query, $sendId) {
            $query->where('type', 'send')->where('send_id', $sendId);
        })->when(isset($status), function ($query) use ($status) {
            $query->where('status', $status);
        })->count();
    }

    public function validUserPlatformCoupon($uid)
    {
        $time = date('Y-m-d H:i:s');
        return StoreCouponUser::getDB()->where('uid', $uid)->where('mer_id', 0)->where('start_time', '<', $time)->where('end_time', '>', $time)
            ->where('is_fail', 0)->where('status', 0)
            ->with(['product' => function ($query) {
                $query->field('coupon_id,product_id');
            }, 'coupon' => function ($query) {
                $query->field('coupon_id,type,send_type');
            }])->order('coupon_price DESC, coupon_user_id ASC')->select();
    }

    public function clear($id,$field)
    {
        $this->getModel()::getDB()->where($field, $id)->delete();
    }

}
