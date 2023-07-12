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

namespace app\common\model\system\merchant;

use app\common\model\BaseModel;

class MerchantIntention extends BaseModel
{
    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tablePk(): string
    {
        return 'mer_intention_id';
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020-03-30
     */
    public static function tableName(): string
    {
        return 'merchant_intention';
    }

    public function setImagesAttr($value)
    {
        return implode(',', $value);
    }

    public function getImagesAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function merchantCategory()
    {
        return $this->hasOne(MerchantCategory::class, 'merchant_category_id', 'merchant_category_id')->bind(['category_name']);
    }

    public function merchantType()
    {
        return $this->hasOne(MerchantType::class, 'mer_type_id', 'mer_type_id')->bind(['type_name']);
    }
}
