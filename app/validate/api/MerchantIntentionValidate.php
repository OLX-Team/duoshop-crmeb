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


namespace app\validate\api;


use think\Validate;

class MerchantIntentionValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'phone|手机号' => 'require|mobile',
        'name|姓名' => 'require',
        'mer_name|姓名' => 'require|max:32',
        'merchant_category_id|商户分类' => 'require',
        'mer_type_id|店铺类型' => 'integer',
        'code|验证码' => 'require',
        'images|资质' => 'array',
    ];
}
