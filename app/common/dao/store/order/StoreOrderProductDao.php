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


namespace app\common\dao\store\order;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\store\order\StoreOrderProduct;
use think\facade\Db;
use think\model\Relation;

/**
 * Class StoreOrderProductDao
 * @package app\common\dao\store\order
 * @author xaboy
 * @day 2020/6/10
 */
class StoreOrderProductDao extends BaseDao
{
    const ORDER_VERIFY_STATUS_ = 1;
    const ORDER_VERIFY_STATUS_SUCCESS = 3;
    /**
     * @return string
     * @author xaboy
     * @day 2020/6/10
     */
    protected function getModel(): string
    {
        return StoreOrderProduct::class;
    }

    /**
     * @param $id
     * @param $uid
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/10
     */
    public function userOrderProduct($id, $uid)
    {
        return StoreOrderProduct::getDB()->where('uid', $uid)->where('order_product_id', $id)->with(['orderInfo' => function (Relation $query) {
            $query->field('order_id,order_sn,mer_id')->where('status', 2);
        }])->find();
    }

    /**
     * @param $orderId
     * @return int
     * @author xaboy
     * @day 2020/6/12
     */
    public function noReplyProductCount($orderId)
    {
        return StoreOrderProduct::getDB()->where('order_id', $orderId)->where('is_refund','<>','3')->where('is_reply', 0)
            ->count();
    }

    /**
     * @param array $ids
     * @param $uid
     * @param null $orderId
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/12
     */
    public function userRefundProducts(array $ids, $uid, $orderId = null)
    {
        return StoreOrderProduct::getDB()->whereIn('order_product_id', $ids)->when($orderId, function ($query, $orderId) {
            return $query->where('order_id', $orderId);
        })->where('uid', $uid)->where('refund_num', '>', 0)->where('refund_switch',1)->select();
    }

    public function orderProductGroup($date, $merId = null, $limit = 7)
    {
        return StoreOrderProduct::getDB()->alias('A')->leftJoin('StoreOrder B', 'A.order_id = B.order_id')
            ->field(Db::raw('sum(A.product_num) as total,A.product_id,cart_info'))
            ->withAttr('cart_info', function ($val) {
                return json_decode($val, true);
            })->when($date, function ($query, $date) {
                getModelTime($query, $date, 'B.pay_time');
            })->when($merId, function ($query, $merId) {
                $query->where('B.mer_id', $merId);
            })->where('B.paid', 1)->group('A.product_id')->limit($limit)->order('total DESC')->select();
    }

    public function dateProductNum($date)
    {
        return StoreOrderProduct::getDB()->alias('A')->leftJoin('StoreOrder B', 'A.order_id = B.order_id')->when($date, function ($query, $date) {
            getModelTime($query, $date, 'B.pay_time');
        })->where('B.paid',1)->sum('A.product_num');
    }

    /**
     * TODO 用户购买活动商品数量
     * @param int $activityId
     * @param int $uid
     * @param int $orderType
     * @return int
     * @author Qinii
     * @day 2020-10-23
     */
    public function getUserPayCount(int $activityId,int $uid,int $productType)
    {
        $query = StoreOrderProduct::hasWhere('orderInfo',function($query){
            //  已支付/未支付
            $query->where('is_del',0)->whereOr(function($query){
                $query->where('paid',1)->where('is_del',1);
            });
        });
        $query->where('uid',$uid)->where('product_type',$productType)->where('activity_id',$activityId);
        $count = $query->count();
        return $count;
    }


    public function getUserPayProduct(?string  $keyword, int $uid)
    {
        $query = StoreOrderProduct::hasWhere('spu',function($query) use($keyword){
            $query->when($keyword, function ($query) use($keyword) {
               $query->whereLike('store_name',"%{$keyword}%");
            });
            $query->where('product_type',0);
        });
        $query->where('uid', $uid)->where('StoreOrderProduct.product_type',0);
        return  $query;
    }

}
