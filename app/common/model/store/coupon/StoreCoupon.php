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


namespace app\common\model\store\coupon;


use app\common\model\BaseModel;
use app\common\model\system\merchant\Merchant;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\product\SpuRepository;

class StoreCoupon extends BaseModel
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'coupon_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'store_coupon';
    }

    public function product()
    {
        return $this->hasMany(StoreCouponProduct::class, 'coupon_id', 'coupon_id');
    }

    public function issue()
    {
        return $this->hasOne(StoreCouponIssueUser::class, 'coupon_id', 'coupon_id');
    }
    public function svipIssue()
    {
        return $this->hasOne(StoreCouponIssueUser::class, 'coupon_id', 'coupon_id')->whereMonth('create_time');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

    public function getUsedNumAttr()
    {
        return app()->make(StoreCouponUserRepository::class)->usedNum($this->coupon_id);
    }

    public function getSendNumAttr()
    {
        return app()->make(StoreCouponUserRepository::class)->sendNum($this->coupon_id);
    }

    public function getProductLstAttr()
    {
        $where['spu_status'] = 1;
        $where['mer_status'] = 1;
        //优惠券类型 0-店铺 1-商品券 10 平台通用券 11平台品类券 12平台跨店券
        switch ($this['type']) {
            case 0:
                $where['mer_id'] = $this->mer_id;
                break;
            case 1:
                $where['product_ids'] = StoreCouponProduct::where('coupon_id', $this->coupon_id)->limit(5)->column('product_id');
                break;
            case 10:
                break;
            case 11:
                $ids = StoreCouponProduct::where('coupon_id', $this->coupon_id)->limit(5)->column('product_id');
                $where['cate_pid'] = $ids;
                break;
            case 12:
                $ids = StoreCouponProduct::where('coupon_id', $this->coupon_id)->limit(5)->column('product_id');
                $where['mer_ids'] = $ids;
                break;
        }

        $where['order'] = 'none';
        $where['common'] = 1;
        $product = app()->make(SpuRepository::class)->search($where)->field(
            'S.spu_id,S.product_id,S.store_name,S.image,activity_id,S.keyword,S.price,S.mer_id,spu_id,S.status,store_info,brand_id,cate_id,unit_name,S.star,S.rank,S.sort,sales,S.product_type,P.ot_price'
        )->limit(3)->orderRand()->select()->toArray();
        return $product;
    }
}
