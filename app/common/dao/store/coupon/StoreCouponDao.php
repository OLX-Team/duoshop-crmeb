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
use app\common\model\store\coupon\StoreCoupon;
use app\common\model\store\coupon\StoreCouponUser;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use think\Collection;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * Class StoreCouponIssueDao
 * @package app\common\dao\store\coupon
 * @author xaboy
 * @day 2020-05-14
 */
class StoreCouponDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return StoreCoupon::class;
    }

    /**
     * @param int $merId
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-05-14
     */
    public function search(?int $merId, array $where)
    {
        if(isset($where['is_trader']) && $where['is_trader'] !== ''){
            $query = StoreCoupon::hasWhere('merchant',function($query)use($where){
                $query->where('is_trader',$where['is_trader']);
            });
        }else{
            $query = StoreCoupon::getDB()->alias('StoreCoupon');
        }
        $query->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('StoreCoupon.status', (int)$where['status']);
        })->when(isset($where['coupon_name']) && $where['coupon_name'] !== '', function ($query) use ($where) {
            $query->whereLike('title', "%{$where['coupon_name']}%");
        })->when(isset($where['coupon_id']) && $where['coupon_id'] !== '', function ($query) use ($where) {
            $query->where('coupon_id', (int)$where['coupon_id']);
        })->when(isset($where['send_type']) && $where['send_type'] !== '', function ($query) use ($where) {
            $query->where('send_type', (int)$where['send_type']);
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', (int)$where['type']);
        })->when($merId !== null, function ($query) use ($merId) {
            $query->where('StoreCoupon.mer_id', $merId);
        })->when(isset($where['is_mer']) &&  $where['is_mer'] !== '', function ($query) use ($merId) {
            $query->where('type', '<', 10);
        });
        return $query->where('StoreCoupon.is_del', 0)->order(($merId ? 'StoreCoupon.sort DESC,' : '') . 'coupon_id DESC');
    }

    /**
     * @param int|null $type
     * @param int $send_type
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/18
     */
    public function validCouponQuery($type = null, $send_type = 0)
    {
        $query = StoreCoupon::getDB()
            ->where('status', 1)
            ->where('send_type', $send_type)
            ->where('is_del', 0)
            ->order('sort DESC,coupon_id DESC')
            ->when(!is_null($type), function ($query) use ($type) {
                $query->where('type', $type);
            });
        $query->where(function (BaseQuery $query) {
            $query->where('is_limited', 0)->whereOr(function (BaseQuery $query) {
                $query->where('is_limited', 1)->where('remain_count', '>', 0);
            });
        })
        ->where(function (BaseQuery $query) {
            $query->where('is_timeout', 0)->whereOr(function (BaseQuery $query) {
                $time = date('Y-m-d H:i:s');
                $query->where('is_timeout', 1)->where('start_time', '<', $time)->where('end_time', '>', $time);
            });
        })
        ->where(function (BaseQuery $query) {
            $query->where('coupon_type', 0)->whereOr(function (BaseQuery $query) {
                $query->where('coupon_type', 1)->where('use_end_time', '>', date('Y-m-d H:i:s'));
            });
        });
        return $query;
    }

    public function validCouponQueryWithMerchant($where,$uid = null)
    {
        $query = StoreCoupon::alias('C')->leftJoin('Merchant M','C.mer_id = M.mer_id')
            ->where('C.status', 1)
            ->where('C.is_del', 0)
            ->when(isset($where['type']) && !is_null($where['type']), function ($query) use ($where) {
                if ($where['type'] == '') {
                    $query->where('C.type', 'in', [0,10,11,12]);
                } else {
                    $query->where('C.type', $where['type']);
                }
            })
            ->when(isset($where['send_type']) && $where['send_type'] !== '', function($query) use($where){
                $query->where('C.send_type', $where['send_type']);
            })
            ->when(isset($where['not_svip']) && $where['not_svip'] !== '', function($query) use($where){
                $query->where('C.send_type', '<>',StoreCouponRepository::GET_COUPON_TYPE_SVIP);
            })
            ->when($uid, function($query) use($uid){
                $couponId = StoreCouponUser::where('uid',$uid)->whereIn('status',[1,2])->column('coupon_id');
                $query->whereNotIn('C.coupon_id', $couponId);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function($query) use($where){
                $query->where('C.mer_id', $where['mer_id']);
            })
            ->where(function (BaseQuery $query) {
                $query->where('is_limited', 0)->whereOr(function (BaseQuery $query) {
                    $query->where('is_limited', 1)->where('remain_count', '>', 0);
                });
            })
            ->where(function (BaseQuery $query) {
                $query->where('is_timeout', 0)->whereOr(function (BaseQuery $query) {
                    $time = date('Y-m-d H:i:s');
                    $query->where('is_timeout', 1)->where('start_time', '<', $time)->where('end_time', '>', $time);
                });
            })
            ->where(function (BaseQuery $query) {
                $query->where('C.mer_id', 0)->whereOr(function (BaseQuery $query) {
                    $query->where('M.is_del',0)->where('M.status',1)->where('M.mer_state',1);
                });
            })
            ->where(function (BaseQuery $query) {
                $query->where('coupon_type', 0)->whereOr(function (BaseQuery $query) {
                    $query->where('coupon_type', 1)->where('use_end_time', '>', date('Y-m-d H:i:s'));
                });
            });
        return $query->order('C.sort DESC,C.create_time DESC');
    }


    /**
     * @param $id
     * @param $uid
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/19
     */
    public function validCoupon($id, $uid)
    {
        return $this->validCouponQuery()->when($uid, function (BaseQuery $query, $uid) {
            $query->with(['issue' => function (BaseQuery $query) use ($uid) {
                $query->where('uid', $uid);
            }]);
        })->where('coupon_id', $id)->find();
    }

    public function validSvipCoupon($id, $uid)
    {
        return $this->validCouponQuery(null,StoreCouponRepository::GET_COUPON_TYPE_SVIP)->when($uid, function (BaseQuery $query, $uid) {
            $query->with(['svipIssue' => function (BaseQuery $query) use ($uid) {
                $query->where('uid', $uid);
            }]);
        })->where('coupon_id', $id)->find();
    }

    /**
     * @param $merId
     * @param null $uid
     * @return Collection
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/1
     */
    public function validMerCoupon($merId, $uid = null, $type = 0)
    {
        return $this->validCouponQuery($type)->when($uid, function (BaseQuery $query, $uid) {
            $query->with(['issue' => function (BaseQuery $query) use ($uid) {
                $query->where('uid', $uid);
            }]);
        })->where('mer_id', $merId)->select();
    }

    /**
     * @param $merId
     * @param null $uid
     * @return int
     * @author xaboy
     * @day 2020/6/19
     */
    public function validMerCouponExists($merId, $uid = null)
    {
        return $this->validCouponQuery(0)->when($uid, function (BaseQuery $query, $uid) {
            $query->with(['issue' => function (BaseQuery $query) use ($uid) {
                $query->where('uid', $uid);
            }]);
        })->where('mer_id', $merId)->count();
    }

    /**
     * @param array $couponIds
     * @param null $uid
     * @return Collection
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/1
     */
    public function validProductCoupon(array $couponIds, $uid = null)
    {
        return $this->validCouponQuery(1)->when($uid, function (BaseQuery $query, $uid) {
            $query->with(['issue' => function (BaseQuery $query) use ($uid) {
                $query->where('uid', $uid);
            }]);
        })->whereIn('coupon_id', $couponIds)->select();
    }

    /**
     * @param array $couponIds
     * @param null $uid
     * @return int
     * @author Qinii
     */
    public function validProductCouponExists(array $couponIds, $uid = null)
    {
        return $this->validCouponQuery(1)->when($uid, function (BaseQuery $query, $uid) {
            $query->with(['issue' => function (BaseQuery $query) use ($uid) {
                $query->where('uid', $uid);
            }]);
        })->whereIn('coupon_id', $couponIds)->count();
    }

    /**
     * @param int $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-05-13
     */
    public function delete(int $id)
    {
        return StoreCoupon::getDB()->where($this->getPk(), $id)->update(['is_del' => 1]);
    }

    /**
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020-05-13
     */
    public function exists(int $id)
    {
        return StoreCoupon::getDB()->where($this->getPk(), $id)->where('is_del', 0)->count($this->getPk()) > 0;
    }

    /**
     * @param int $merId
     * @param int $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-05-13
     */
    public function merDelete(int $merId, int $id)
    {
        return StoreCoupon::getDB()->where($this->getPk(), $id)->where('mer_id', $merId)->update(['is_del' => 1]);
    }

    /**
     * @param int $merId
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020-05-13
     */
    public function merExists(int $merId, int $id)
    {
        return StoreCoupon::getDB()->where($this->getPk(), $id)->where('mer_id', $merId)->where('is_del', 0)->count($this->getPk()) > 0;
    }

    /**
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/18
     */
    public function newPeopleCoupon()
    {
        return $this->validCouponQuery(null, 2)->select();
    }

    /**
     * @param array|null $ids
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/19
     */
    public function getGiveCoupon(array $ids = null)
    {
        return $this->validCouponQuery(null, 3)->when($ids, function ($query, $ids) {
            $query->whereIn('coupon_id', $ids);
        })->select();
    }

    public function clear($id,$field)
    {
        $this->getModel()::getDB()->where($field, $id)->delete();
    }

}
