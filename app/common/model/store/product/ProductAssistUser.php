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

class ProductAssistUser extends BaseModel
{
    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tablePk(): string
    {
        return 'product_assist_user_id';
    }


    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-10-12
     */
    public static function tableName(): string
    {
        return 'store_product_assist_user';
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
        return $this->hasOne(Merchant::class, 'mer_id', 'mer_id');
    }

//    public function getAvatarImgAttr($value)
//    {
//        if(!$value){
//            $value = '/static/f.png';
//        }
//        return $value;
//    }

    public function searchProductAssistSetIdAttr($query,$value)
    {
        $query->where('product_assist_set_id',$value);
    }
    public function searchUidAttr($query,$value)
    {
        $query->where('uid',$value);
    }
    public function searchProductAssistIdAttr($query,$value)
    {
        $query->where('product_assist_id',$value);
    }
}
