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


use app\common\dao\store\order\StoreCartDao;
use app\common\model\store\product\Product;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\coupon\StoreCouponProductRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\product\ProductRepository;
use think\exception\ValidateException;

/**
 * Class StoreCartRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/5/30
 * @mixin StoreCartDao
 */
class StoreCartRepository extends BaseRepository
{
    /**
     * StoreCartRepository constructor.
     * @param StoreCartDao $dao
     */
    public function __construct(StoreCartDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $uid
     * @return array
     * @author Qinii
     */
    public function getList($user)
    {
        $res = $this->dao->getAll($user->uid)->append(['checkCartProduct', 'UserPayCount', 'ActiveSku', 'spu']);
        $make = app()->make(ProductRepository::class);
        $res->map(function ($item) use ($make) {
            $item['attr'] = $make->detailAttr($item['attr']);
        });
        return $this->checkCartList($res, $user->uid, $user);
    }

    public function checkCartList($res, $hasCoupon = 0, $user = null)
    {
        $arr = $fail = [];
        $product_make = app()->make(ProductRepository::class);
        $svip_status = ($user && $user->is_svip > 0 && systemConfig('svip_switch_status')) ? true : false;
        foreach ($res as $item) {
            if (!$item['checkCartProduct']) {
                $item['product'] = $product_make->getFailProduct($item['product_id']);
                $fail[] = $item;
            } else {
                //商户信息
                if ($item['merchant']){
                    $merchantData = $item['merchant']->append(['openReceipt'])->toArray();
                } else {
                    $merchantData = ['mer_id' => 0];
                }
                unset($item['merchant']);
                $coupon_make = app()->make(StoreCouponRepository::class);
                if (!isset($arr[$item['mer_id']])) {
                    if ($hasCoupon)
                        $merchantData['hasCoupon'] = $coupon_make->validMerCouponExists($item['mer_id'], $hasCoupon);
                    $arr[$item['mer_id']] = $merchantData;
                }
                if ($hasCoupon && !$arr[$item['mer_id']]['hasCoupon']) {
                    $couponIds = app()->make(StoreCouponProductRepository::class)->productByCouponId([$item['product']['product_id']]);
                    $arr[$item['mer_id']]['hasCoupon'] = count($couponIds) ? $coupon_make->validProductCouponExists([$item['product']['product_id']], $hasCoupon) : 0;
                }
                if ($svip_status && $item['product']['show_svip_price']) {
                    $item['productAttr']['show_svip_price'] = true;
                    $item['productAttr']['org_price'] = $item['productAttr']['price'];
                    $item['productAttr']['price'] = $item['productAttr']['svip_price'];
                } else {
                    $item['productAttr']['show_svip_price'] = false;
                }
                $arr[$item['mer_id']]['list'][] = $item;
            }
        }
        $list = array_values($arr);
        return compact('list', 'fail');
    }

    /**
     * 获取单条购物车信息
     * @Author:Qinii
     * @Date: 2020/5/30
     * @param int $id
     * @return mixed
     */
    public function getOne(int $id,int $uid)
    {
        $where = [$this->dao->getPk() => $id,'is_del'=>0,'is_fail'=>0,'is_new'=>0,'is_pay'=>0,'uid' => $uid];
        return ($this->dao->getWhere($where));
    }

    /**
     *  查看相同商品的sku是存在
     * @param $sku
     * @param $uid
     * @author Qinii
     */
    public function getCartByProductSku($sku,$uid)
    {
        $where = ['is_del'=>0,'is_fail'=>0,'is_new'=>0,'is_pay'=>0,'uid' => $uid,'product_type' => 0,'product_attr_unique' => $sku];
        return ($this->dao->getWhere($where));
    }


    public function getProductById($productId)
    {
        $where = [
            'is_del' =>0,
            'is_new'=>0,
            'is_pay'=>0,
            'product_id'=>$productId
        ];
        return $this->dao->getWhereCount($where);
    }
}
