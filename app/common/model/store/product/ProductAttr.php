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

class ProductAttr extends BaseModel
{

    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tablePk(): string
    {
        return '';
    }


    /**
     * @Author:Qinii
     * @Date: 2020/5/8
     * @return string
     */
    public static function tableName(): string
    {
        return 'store_product_attr';
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/9
     * @param $value
     * @return array
     */
    public function getAttrValuesAttr($value)
    {
        return explode('-!-',$value);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/9
     * @param $value
     * @return string
     */
    public function setAttrValuesAttr($value)
    {
        return implode('-!-',$value);
    }

}
