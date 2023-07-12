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
use app\common\model\store\order\StoreOrderProduct;
use app\common\model\system\merchant\Merchant;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\product\SpuRepository;

class ProductGroup extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tablePk(): string
    {
        return 'product_group_id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 1/7/21
     */
    public static function tableName(): string
    {
        return 'store_product_group';
    }

    public function product()
    {
        return $this->hasOne(Product::class,'product_id','product_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }

    public function groupBuying()
    {
        return $this->hasMany(ProductGroupBuying::class,'product_group_id','product_group_id');
    }

    public function activeSku()
    {
        return $this->hasMany(ProductGroupSku::class,'product_group_id','product_group_id');
    }


    public function getActionStatusAttr($value)
    {
        if($value== -1) return -1;
        $start_time = strtotime($this->start_time);
        $end_time = strtotime($this->end_time);
        if($start_time > time()) return 0;


        if($start_time <= time() && $end_time > time()){
            $this->action_status = 1;
            $this->save();
            return 1;
        }
        if($end_time <= time()){
            $this->action_status = -1;
            $this->save();
            queue(ChangeSpuStatusJob::class, ['id' => $this->product_group_id, 'product_type' => 4]);
            //app()->make(SpuRepository::class)->changeStatus($this->product_group_id,4);
            return -1;
        }
    }

    public function getStockAttr()
    {
        return ProductGroupSku::where('product_group_id',$this->product_group_id)->sum('stock');
    }

    public function getStockCountAttr()
    {
        return ProductGroupSku::where('product_group_id',$this->product_group_id)->sum('stock_count');
    }

    public function getUsStatusAttr()
    {
        return ($this->product_status == 1) ? ($this->status == 1 ? ( $this->is_show ? 1 : 0 ) : -1) : -1;
    }

    //销量
    public function getSalesAttr()
    {
        $make = app()->make(StoreOrderRepository::class);
        $where = [
            'product_id' => $this->product_id,
            'product_type' => 4,
        ];
        return $make->getTattendCount($where,null)->sum('product_num');
    }

    //参与人次:所有（含待付款）
    public function getCountTakeAttr()
    {
        return StoreOrderProduct::where('product_id',$this->product_id)->where('product_type',4)->count();
    }

    //成团人数数量: 成功的团，真实人数
    public function getCountUserAttr()
    {
        return ProductGroupUser::where('product_group_id',$this->product_group_id)
            ->where('uid','<>',0)->where('status',10)->count();
    }

    public function getStarAttr()
    {
        return Spu::where('product_type',4)->where('activity_id',$this->product_group_id)->value('star');
    }


    public function searchProductStatusAttr($query,$value)
    {
        if($value == -1){
            $query->where('product_status','in',[-1,-2]);
        }else {
            $query->where('product_status', $value);
        }
    }

    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchStatusAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }

    public function searchProductGroupIdAttr($query,$value)
    {
        $query->where('product_group_id',$value);
    }

}
