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
use app\common\model\store\coupon\StoreCouponProduct;
use app\common\model\store\StoreSeckillActive;
use app\common\model\system\merchant\Merchant;
use think\db\BaseQuery;

class Spu extends BaseModel
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 12/18/20
     */
    public static function tablePk(): string
    {
        return 'spu_id';
    }

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 12/18/20
     */
    public static function tableName(): string
    {
        return 'store_spu';
    }


    /*
     * -----------------------------------------------------------------------------------------------------------------
     * 属性
     * -----------------------------------------------------------------------------------------------------------------
     */
    public function getMinExtensionAttr($value)
    {
        if(!isset($this->product)) return 0;
        if($this->product->extension_type){
            $org_extension = ($this->product->attrValue()->order('extension_two ASC')->value('extension_two'));
        } else {
            $org_extension = bcmul(($this->product->attrValue()->order('price ASC')->value('price')) , systemConfig('extension_one_rate'),2);
        }
        $spreadUser = (request()->hasMacro('isLogin') && request()->isLogin() &&  request()->userType() == 1  ) ? request()->userInfo() : null;
        if ($spreadUser && $spreadUser->brokerage_level > 0 && $spreadUser->brokerage && $spreadUser->brokerage->extension_one_rate > 0) {
            $org_extension = bcmul($org_extension, 1 + $spreadUser->brokerage->extension_one_rate, 2);
        }
        return $org_extension;
    }

    public function getMaxExtensionAttr($value)
    {
        if(!isset($this->product)) return 0;
        if($this->product->extension_type){
            return  ($this->product->attrValue()->order('extension_two DESC')->value('extension_one'));
        } else {
            return  bcmul(($this->product->attrValue()->order('price DESC')->value('price')) , systemConfig('extension_one_rate'),2);
        }
        $spreadUser = (request()->hasMacro('isLogin') && request()->isLogin() &&  request()->userType() == 1  ) ? request()->userInfo() : null;
        if ($spreadUser && $spreadUser->brokerage_level > 0 && $spreadUser->brokerage && $spreadUser->brokerage->extension_one_rate > 0) {
            $org_extension = bcmul($org_extension, 1 + $spreadUser->brokerage->extension_one_rate, 2);
        }
        return $org_extension;
    }

    public function getStopTimeAttr()
    {
        if($this->product_type == 1){
            if (is_null($this->seckillActive)) {
                return date('Y-m-d H:i:s',strtotime("-1 day"));
            }
            $day = date('Y-m-d',time());
            $_day = strtotime($day);
            $end_day = strtotime($this->seckillActive['end_day']);
            if($end_day >= $_day)
                return strtotime($day.$this->seckillActive['end_time'].':00:00');
            if($end_day < strtotime($day))
                return strtotime(date('Y-m-d',$end_day).$this->seckillActive['end_time'].':00:00');
        }
    }

    public function setMerLabelsAttr($value)
    {
        if (!empty($value)) {
            if (!is_array($value))
                return ','. $value .',';
            return ','. implode(',', $value) .',';
        }
        return $value;
    }

    public function getMerLabelsAttr($value)
    {
        if (!$value) return [];
        return explode(',',rtrim(ltrim($value,','),','));
    }


    public function setSysLabelsAttr($value)
    {
        if (!empty($value)) {
            if (!is_array($value))
                return ','. $value .',';
            return ','. implode(',', $value) .',';
        }
        return $value;
    }

    public function getSysLabelsAttr($value)
    {
        if (!$value) return [];
        return explode(',',rtrim(ltrim($value,','),','));
    }

    public function getImageAttr($value)
    {
        if (is_int(strpos($value, 'http'))) {
            return $value;
        } else {
            return rtrim(systemConfig('site_url'), '/') . $value;
        }
    }

    public function getOtPriceAttr($value)
    {
        return (int)$value;
    }

    /**
     * TODO 是否展示会员价
     * @return array
     * @author Qinii
     * @day 2023/1/4
     */
    public function getShowSvipInfoAttr($value, $data)
    {
        if ($this->product_type == 0) return  $this->product->show_svip_info;
    }

    /**
     * TODO 获取会员价
     * @return int|string
     * @author Qinii
     * @day 2023/1/4
     */
    public function getSvipPriceAttr()
    {
        if ($this->product_type == 0) return $this->product->svip_price;
    }

    public function getIsSvipPriceAttr()
    {
        if ($this->product_type == 0) return $this->product->is_svip_price;
    }

    /*
     * -----------------------------------------------------------------------------------------------------------------
     * 关联表
     * -----------------------------------------------------------------------------------------------------------------
     */
    public function product()
    {
        return $this->hasOne(Product::class,'product_id','product_id');
    }
    public function merchant()
    {
        return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }
    public function issetCoupon()
    {
        return $this->hasOne(StoreCouponProduct::class, 'product_id', 'product_id')->alias('A')
            ->rightJoin('StoreCoupon B', 'A.coupon_id = B.coupon_id')
            ->where(function (BaseQuery $query) {
                $query->where('B.is_limited', 0)->whereOr(function (BaseQuery $query) {
                    $query->where('B.is_limited', 1)->where('B.remain_count', '>', 0);
                });
            })->where(function (BaseQuery $query) {
                $query->where('B.is_timeout', 0)->whereOr(function (BaseQuery $query) {
                    $time = date('Y-m-d H:i:s');
                    $query->where('B.is_timeout', 1)->where('B.start_time', '<', $time)->where('B.end_time', '>', $time);
                });
            })->field('A.product_id,B.*')->where('status', 1)->where('type', 1)->where('send_type', 0)->where('is_del', 0)
            ->order('sort DESC,coupon_id DESC')->hidden(['is_del', 'status']);
    }
    public function merCateId()
    {
        return $this->hasMany(ProductCate::class,'product_id','product_id')->field('product_id,mer_cate_id');
    }
    public function seckillActive()
    {
        return $this->hasOne(StoreSeckillActive::class,'seckill_active_id','activity_id');
    }

    /*
     * -----------------------------------------------------------------------------------------------------------------
     * 搜索器
     * -----------------------------------------------------------------------------------------------------------------
     */
    public function searchMerIdAttr($query,$value)
    {
        $query->where('mer_id',$value);
    }
    public function searchProductIdAttr($query,$value)
    {
        $query->where('product_id',$value);
    }
    public function searchProductTypeAttr($query,$value)
    {
        $query->where('product_type',$value);
    }
    public function searchActivityIdAttr($query,$value)
    {
        $query->where('activity_id',$value);
    }
    public function searchKeyworkAttr($query,$value)
    {
        $query->whereLike('store_name|keyword',$value);
    }
    public function searchPriceOnAttr($query, $value)
    {
        $query->where('price','>=',$value);
    }
    public function searchPriceOffAttr($query, $value)
    {
        $query->where('price','<=',$value);
    }
    public function searchSpuIdsAttr($query, $value)
    {
        $query->whereIn('spu_id',$value);
    }
}
