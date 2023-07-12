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

class UserReceiptValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'receipt_type|发票类型' => 'require|in:1,2',
        'receipt_title|发票抬头' => 'require',
        'receipt_title_type|发票抬头类型' => 'require|in:1,2',
        'duty_paragraph|税号' => 'requireIf:receipt_title_type,2|alphaNum',
        'email|邮箱' => 'requireIf:receipt_type,1|email',
        'bank_name|开户行' => 'requireIf:receipt_type,2',
        'bank_code|银行卡号' => 'requireIf:receipt_type,2|number',
        'address|企业地址' => 'requireIf:receipt_type,2',
        'tel|企业电话' => 'requireIf:receipt_type,2',
    ];

}
