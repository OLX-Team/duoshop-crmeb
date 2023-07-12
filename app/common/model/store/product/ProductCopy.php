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
use app\common\model\store\StoreCategory;
use app\common\model\system\merchant\Merchant;

class ProductCopy extends BaseModel
{

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tablePk(): string
    {
        return 'store_product_copy_id';
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tableName(): string
    {
        return 'store_product_copy';
    }

    public function getInfoAttr($value)
    {
        return json_decode($value) ?: $value;
    }

    public function setInfoAttr($value)
    {
        return json_encode($value,JSON_UNESCAPED_UNICODE);
    }

    public function merchant()
    {
       return $this->hasOne(Merchant::class,'mer_id','mer_id');
    }
}
