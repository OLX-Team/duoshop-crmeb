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
use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductAssistUserRepository;
use app\common\repositories\store\product\SpuRepository;

class ProductAssist extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tablePk(): string
    {
        return 'product_assist_id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tableName(): string
    {
        return 'store_product_assist';
    }

    public function product()
    {
        return $this->hasOne(Product::class,'product_id','product_id');
    }

    public function assistSku()
    {
        return$this->hasMany(ProductAssistSku::class,'product_assist_id','product_assist_id');
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
    public function getAssistStatusAttr()
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
            if($this->product_status != 1 || $this->status != 1 || $this->is_show != 1) return 0;
            return 1;
        }
        //已结束
        if($end_time <= $time) {
            $this->action_status = -1;
            $this->save();
            app()->make(SpuRepository::class)->changeStatus($this->product_assist_id,3);
            return 2;
        }
    }

    public function getStarAttr()
    {
        return Spu::where('product_type',3)->where('activity_id',$this->product_assist_id)->value('star');
    }

    public function getUsStatusAttr()
    {
        return ($this->product_status == 1) ? ($this->status == 1 ? ( $this->is_show ? 1 : 0 ) : -1) : -1;
    }

    public function getUserCountAttr()
    {
        return ProductAssistUser::where('product_assist_id',$this->product_assist_id)->count() + ProductAssistSet::where('product_assist_id',$this->product_assist_id)->count();
    }

    /**
     * TODO 助力成功人数
     * @return mixed
     * @author Qinii
     * @day 2020-10-30
     */
    public function getSuccessAttr()
    {
        $make = app()->make(ProductAssistSetRepository::class);
        $where = [
            'product_assist_id' => $this->product_assist_id,
            'status' => [10, 20]
        ];
        return $make->getSearch($where)->count();
    }

    /**
     * TODO 支付成功人数
     * @return mixed
     * @author Qinii
     * @day 2020-10-30
     */
    public function getPayAttr()
    {
        $make = app()->make(ProductAssistSetRepository::class);
        $where = [
            'product_assist_id' => $this->product_assist_id,
            'status' => 20
        ];
        return $make->getSearch($where)->count();
    }

    /**
     * TODO 助力人数
     * @return mixed
     * @author Qinii
     * @day 2020-10-30
     */
    public function getAllAttr()
    {
        $make = app()->make(ProductAssistUserRepository::class);
        $where = [
            'product_assist_id' => $this->product_assist_id,
        ];
        return $make->getSearch($where)->count();
    }

    public function getStockCountAttr()
    {
        return ProductAssistSku::where('product_assist_id',$this->product_assist_id)->sum('stock_count');
    }

    public function getStockAttr()
    {
        return ProductAssistSku::where('product_assist_id',$this->product_assist_id)->sum('stock');
    }
}
