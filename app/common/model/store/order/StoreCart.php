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


namespace app\common\model\store\order;


use app\common\model\BaseModel;
use app\common\model\store\product\Product;
use app\common\model\store\product\ProductAssistSet;
use app\common\model\store\product\ProductAttr;
use app\common\model\store\product\ProductAttrValue;
use app\common\model\store\product\ProductGroup;
use app\common\model\store\product\ProductPresell;
use app\common\model\store\product\ProductPresellSku;
use app\common\model\store\product\ProductSku;
use app\common\model\store\product\Spu;
use app\common\model\store\product\StoreDiscounts;
use app\common\model\system\merchant\Merchant;
use app\common\repositories\store\order\StoreOrderProductRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\product\ProductAssistSkuRepository;
use app\common\repositories\store\product\ProductAttrValueRepository;
use app\common\repositories\store\product\ProductGroupSkuRepository;
use app\common\repositories\store\product\ProductPresellSkuRepository;
use app\common\repositories\store\product\ProductSkuRepository;
use app\common\repositories\store\StoreSeckillActiveRepository;
use function Symfony\Component\String\b;

class StoreCart extends BaseModel
{

    public static function tablePk(): ?string
    {
        return 'cart_id';
    }

    public static function tableName(): string
    {
        return 'store_cart';
    }

    public function searchCartIdAttr($query,$value)
    {
        $query->where('cart_id',$value);
    }


    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }

    public function productAttr()
    {
        return $this->hasOne(ProductAttrValue::class, 'unique', 'product_attr_unique');
    }

    public function attr()
    {
        return $this->hasMany(ProductAttr::class,'product_id','product_id');
    }

    public function attrValue()
    {
        return $this->hasMany(ProductAttrValue::class, 'product_id', 'product_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }


    public function productPresell()
    {
        return $this->hasOne(ProductPresell::class,'product_presell_id','source_id');
    }

    public function productDiscount()
    {
        return $this->hasOne(StoreDiscounts::class, 'discount_id', 'source_id');
    }

    public function getProductDiscountAttrAttr()
    {
        return app()->make(ProductSkuRepository::class)->getSearch(['active_id' => $this->source_id, 'unique' => $this->product_attr_unique,'active_type'=> 10])->find();
    }

    public function productAssistSet()
    {
        return $this->hasOne(ProductAssistSet::class,'product_assist_set_id','source_id');
    }

    public function getProductPresellAttrAttr()
    {
        return app()->make(ProductPresellSkuRepository::class)->getSearch(['product_presell_id' => $this->source_id, 'unique' => $this->product_attr_unique])->find();
    }

    public function getProductAssistAttrAttr()
    {
        $make = app()->make(ProductAssistSkuRepository::class);
        $where = [
            "product_assist_id" => $this->productAssistSet->product_assist_id,
            "unique" => $this->product_attr_unique
        ];
        return $make->getSearch($where)->find();
    }


    /**
     * TODO 活动商品 SKU
     * @return array|\think\Model|null
     * @author Qinii
     * @day 1/13/21
     */
    public function getActiveSkuAttr()
    {
        switch ($this->product_type)
        {
            case 2:
                $make = app()->make(ProductPresellSkuRepository::class);
                $where['product_presell_id'] = $this->source_id;
                break;
            case 3:
                $make = app()->make(ProductAssistSkuRepository::class);
                $where['product_assist_id'] = $this->productAssistSet->product_assist_id;
                break;
            case 4:
                $make = app()->make(ProductGroupSkuRepository::class);
                $where['product_group_id'] = $this->product->productGroup->product_group_id;
                break;
            default:
                $make = app()->make(ProductAttrValueRepository::class);
                $where['product_id'] = $this->product_id;
                break;
        }
        $where['unique'] = $this->product_attr_unique;
        return $make->getSearch($where)->find();
    }

    public function getSpuAttr()
    {
        if ($this->product_type) {
            $where = [
                'activity_id' => $this->source_id,
                'product_type' => $this->product_type,
            ];
        } else {
            $where = [
                'product_id' => $this->product_id,
                'product_type' => $this->product_type,
            ];
        }
        return Spu::where($where)->field('spu_id,store_name')->find();
    }
    /**
     * TODO 检测商品是否有效
     * @return bool
     * @author Qinii
     * @day 2020-10-29
     */
    public function getCheckCartProductAttr()
    {
        if($this->is_fail == 1) return false;

        if(is_null($this->product) || is_null($this->productAttr) || $this->product->status !== 1 || $this->product->mer_status !== 1 || $this->product->is_del !== 0)
        {$this->is_fail = 1;$this->save();return false;}

        switch ($this->product_type)
        {
            case 0: //普通商品
                if ($this->product->product_type !==  0 || $this->product->is_show !== 1 || $this->productAttr->stock < $this->cart_num || $this->product->is_used !== 1) {
                    return false;
                }
                break;
            case 1: //秒杀商品
                if ($this->product->product_type !== 1 || $this->product->is_show !== 1) return false;
                //结束时间
                if ($this->product->end_time < time()) return false;
                //限量
                $order_make = app()->make(StoreOrderRepository::class);
                $count = $order_make->seckillOrderCounut($this->product_id);
                if ($this->productAttr->stock <= $count) return false;

                //原商品sku库存
                $value_make = app()->make(ProductAttrValueRepository::class);
                $sku = $value_make->getWhere(['sku' => $this->productAttr->sku, 'product_id' => $this->product->old_product_id]);
                if (!$sku || $sku['stock'] <= 0) return false;

                break;

            case 2: //预售商品
                if($this->source !== 2 || $this->product->product_type !== 2) return false;
                if($this->productPresell->status !== 1 ||
                    $this->productPresell->is_show !== 1 ||
                    $this->productPresell->is_del !== 0 ||
                    $this->productPresell->presell_status !== 1)
                {$this->is_fail = 1;$this->save();return false;}

                $sku = $this->ActiveSku;
                if(!$sku || !$sku->sku()) {$this->is_fail = 1; $this->save(); return false; }

                //库存不足
                if($sku->stock < $this->cart_num || $sku->sku->stock < $this->cart_num) return false;
                break;

            case 3: //助力商品
                if($this->source !== 3 ||  $this->product->product_type !== 3 || ($this->productAssistSet->assist_count !== $this->productAssistSet->yet_assist_count)) return false;
                if(
                    $this->productAssistSet->stop_time < time() ||
                    $this->productAssistSet->sataus === -1 ||
                    !$this->productAssistSet->assist->is_show ||
                    $this->productAssistSet->assist->is_del !== 0 ||
                    $this->productAssistSet->assist->status !== 1)
                {$this->is_fail = 1;$this->save();return false;}
                $sku = $this->ActiveSku;
                if(!$sku || !$sku->sku()) { $this->is_fail = 1; $this->save(); return false; }
                //库存不足
                if($sku->stock < $this->cart_num || $sku->sku->stock < $this->cart_num) return false;
                break;
            case 4:
                if($this->source !== 4 ||  $this->product->product_type !== 4 ) return false;
                $sku = $this->ActiveSku;
                if(!$sku || !$sku->sku()) { $this->is_fail = 1; $this->save(); return false; }
                //库存不足
                if($sku->stock < $this->cart_num || $sku->sku->stock < $this->cart_num) return false;
                break;
        }
        return true;
    }

    /**
     * TODO
     * @return bool
     * @author Qinii
     * @day 2020-10-29
     */
    public function getUserPayCountAttr()
    {
        $make = app()->make(StoreOrderRepository::class);
        switch ($this->product_type)
        {
            case 1: //秒杀
                if(!$make->getDayPayCount($this->uid,$this->product_id) || !$make->getPayCount($this->uid,$this->product_id))
                    return false;
                break;
            case 2: //预售
                $count = $this->productPresell->pay_count;
                if($count == 0) return true;
                $tattend = [
                    'activity_id' => $this->source_id,
                    'product_type' => 2,
                ];
                $pay_count = $make->getTattendCount($tattend,$this->uid)->sum('total_num');
                if($pay_count < $count) return false;
                if (($count - $pay_count)  < $this->cart_num) return false;

                break;

            case 3: //助力
                $tattend = [
                    'activity_id' => $this->source_id,
                    'product_type' => 3,
                ];
                $pay_count = $make->getTattendCount($tattend,$this->uid)->count();
                if($pay_count) return false;

                $count = $this->productAssistSet->assist->pay_count;
                if($count !== 0){
                    $_tattend = [
                        'exsits_id' => $this->productAssistSet->assist->product_assist_id,
                        'product_type' => 3,
                    ];
                    $_count = $make->getTattendCount($_tattend,$this->uid)->count();
                    if($_count >= $count) return false;
                }
                break;
            case 4:
                $tattend = [
                    'exsits_id' => $this->product_id,
                    'product_type' => 4,
                ];
                $pay_count = $make->getTattendCount($tattend,$this->uid)->count();
                if($pay_count) return false;
                $count = $this->product->productGroup->pay_count;
                if($count !== 0){
                    $_tattend = [
                        'exsits_id' => $this->product_id,
                        'product_type' => 34,
                    ];
                    $_count = $make->getTattendCount($_tattend,$this->uid)->count();
                    if($_count >= $count) return false;
                }
                break;
        }

        return true;
    }

    public function searchProductIdAttr($query,$value)
    {
        $query->where('product_id',$value);
    }
    public function searchUidAttr($query,$value)
    {
        $query->where('uid',$value);
    }
    public function searchIsNewAttr($query,$value)
    {
        $query->where('is_new',$value);
    }
    public function searchIsPayAttr($query,$value)
    {
        $query->where('is_pay',$value);
    }
    public function searchIsDelAttr($query,$value)
    {
        $query->where('is_del',$value);
    }
    public function searchIsFailAttr($query,$value)
    {
        $query->where('is_fail',$value);
    }

}
