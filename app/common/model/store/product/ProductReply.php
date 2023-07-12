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
use app\common\repositories\store\product\ProductAttrValueRepository;

/**
 * Class ProductReply
 * @package app\common\model\store\product
 * @author xaboy
 * @day 2020/5/30
 */
class ProductReply extends BaseModel
{

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tablePk(): string
    {
        return 'reply_id';
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tableName(): string
    {
        return 'store_product_reply';
    }

    public function getPicsAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function setPicsAttr($value)
    {
        return $value ? implode(',', $value) : '';
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }

    public function orderProduct()
    {
        return $this->hasOne(StoreOrderProduct::class,'order_product_id','order_product_id');
    }

    /**
     * TODO 用户昵称处理
     * @param $value
     * @return string
     * @author Qinii
     * @day 2022/11/28\
     */
    public function getNicnameAttr($value)
    {
        if (strlen($value) > 1) {
            $str = mb_substr($value,0,1) . '*';
            if (strlen($value) > 2) {
                $str .= mb_substr($value, -1,1);
            }
            return $str;
        }
        return $value;
    }

}
