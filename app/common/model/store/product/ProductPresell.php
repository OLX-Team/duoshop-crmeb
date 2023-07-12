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

namespace app\common\model\store\product;

use app\common\model\BaseModel;
use app\common\model\system\merchant\Merchant;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\product\SpuRepository;

class ProductPresell extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tablePk(): string
    {
        return 'product_presell_id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tableName(): string
    {
        return 'store_product_presell';
    }

    public function product()
    {
        return $this->hasOne(Product::class,'product_id','product_id');
    }

    public function presellSku()
    {
        return$this->hasMany(ProductPresellSku::class,'product_presell_id','product_presell_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }

    /**
     * TODO 状态
     * @return int
     * @author Qinii
     * @day 2020-10-14
     */
    public function getPresellStatusAttr()
    {
        $start_time = strtotime($this->start_time);
        $end_time = strtotime($this->end_time);
        $time = time();
        //已结束
        if($this->action_status == -1) return 2;
        //未开始
        if($start_time > $time) return 0;
        //进行中
        if($start_time <= $time && $end_time > $time) {
            if($this->product_status !== 1 || $this->status !==1 || $this->is_show !== 1) return 0;
            return 1;
        }
        //已结束
        if($end_time <= $time) {
            if($this->presell_type == 1 || ($this->presell_type == 2 && (strtotime($this->final_end_time) < $time))){
                $this->action_status = -1;
                $this->save();
            }
            app()->make(SpuRepository::class)->changeStatus($this->product_presell_id,2);
            return 2;
        }
    }

    public function getStarAttr()
    {
        return Spu::where('product_type',2)->where('activity_id',$this->product_presell_id)->value('star');
    }

    public function getUsStatusAttr()
    {
        return ($this->product_status == 1) ? ($this->status == 1 ? ( $this->is_show ? 1 : 0 ) : -1) : -1;
    }

    /**
     * TODO 第一阶段 参与人数
     * @return mixed
     * @author Qinii
     * @day 2020-10-30
     */
    public function getTattendOneAttr()
    {
        $data['all'] = ProductPresellSku::where('product_presell_id',$this->product_presell_id)->sum('one_take');
        $data['pay']= ProductPresellSku::where('product_presell_id',$this->product_presell_id)->sum('one_pay');
        return $data;
    }

    /**
     * TODO 第二阶段 参与人数
     * @return mixed
     * @author Qinii
     * @day 2020-10-30
     */
    public function getTattendTwoAttr()
    {
        $data['all'] = 0;
        $data['pay'] = 0;
        if($this->presell_type == 2){
            $data['all'] = ProductPresellSku::where('product_presell_id',$this->product_presell_id)->sum('one_pay');
            $data['pay']= ProductPresellSku::where('product_presell_id',$this->product_presell_id)->sum('two_pay');
        }
        return $data;
    }

    /**
     * TODO 获取一张店铺优惠券
     * @return array|\think\Model|null
     * @author Qinii
     * @day 2020-10-30
     */
    public function getCouponAttr()
    {
        $make = app()->make(StoreCouponRepository::class);
        return $coupon = $make->validCouponQuery(0,0)->where('mer_id',$this->mer_id)->find();
    }

    public function getSelesAttr()
    {
        return ProductPresellSku::where('product_presell_id',$this->product_presell_id)->sum('seles');
    }

    public function getStockAttr()
    {
        return ProductPresellSku::where('product_presell_id',$this->product_presell_id)->sum('stock');
    }

    public function getStockCountAttr()
    {
        return ProductPresellSku::where('product_presell_id',$this->product_presell_id)->sum('stock_count');
    }
}
