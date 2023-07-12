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
use app\common\model\user\User;
use app\common\repositories\store\product\ProductAssistUserRepository;
use app\common\repositories\system\merchant\MerchantRepository;

class ProductAssistSet extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tablePk(): string
    {
        return 'product_assist_set_id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tableName(): string
    {
        return 'store_product_assist_set';
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

    public function assist()
    {
        return $this->hasOne(ProductAssist::class,'product_assist_id','product_assist_id');
    }

    public function getStopTimeAttr()
    {
        return isset($this->assist->end_time) ? strtotime($this->assist->end_time) : '';
    }

    public function user()
    {
        return $this->hasOne(User::class,'uid','uid');
    }

    public function getCheckAttr()
    {
        if(in_array($this->status,[0,1])){
            if($this->assist_count == $this->yet_assist_count){
                $this->status = 10;
                $this->save();
            }else{
                if(isset($this->stop_time) && $this->stop_time < time()){
                    $this->status = -1;
                    $this->save();
                }
            }
        }
        return true;
    }

    /**
     * TODO 所有助力者数量
     * @return mixed
     * @author Qinii
     * @day 2020-10-30
     */
    public function getUserCountAttr()
    {
        $make = app()->make(ProductAssistUserRepository::class);
        return $make->getSearch(['product_assist_set_id' => $this->product_assist_set_id])->count();
    }

    public function getViweNumAttr()
    {
        return self::getDB()->sum('view_num');
    }

    public function getShareNumAttr()
    {
        return self::getDB()->sum('share_num');
    }

    public function searchProductAssistIdAttr($query,$value)
    {
        $query->where('product_assist_id',$value);
    }
    public function searchProductAssistSetIdAttr($query,$value)
    {
        $query->where('product_assist_set_id',$value);
    }
    public function searchUidAttr($query,$value)
    {
        $query->where('uid',$value);
    }
    public function searchStatusAttr($query, $value)
    {
        is_array($value) ? $query->whereIn('status', $value) : $query->where('status', $value);
    }
    public function searchUserNameAttr($query,$value)
    {
        $uid = User::whereLike('nickname',"%{$value}%")->column('uid');
        $query->where('uid','in',$uid);
    }
    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }
    public function searchIsTraderAttr($query,$value)
    {
        $make = app()->make(MerchantRepository::class);
        $mer_id = $make->search(['is_trader' => $value])->column('mer_id');
        $query->where('mer_id','in',$mer_id);
    }

    public function searchDateAttr($query,$value)
    {
        getModelTime($query,$value);
    }

    public function searchKeywordAttr($query,$value)
    {
        $id = ProductAssist::whereLike('store_name',"%{$value}%")->column('product_assist_id');
        $query->where(function($query) use ($value, $id){
            $query->where('product_assist_id','in',$id)->whereOr('product_assist_set_id',$value);
        });
    }
}
