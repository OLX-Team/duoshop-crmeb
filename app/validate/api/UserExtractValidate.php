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

class UserExtractValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'wechat|微信号' => 'requireIf:extract_type,1',
        'real_name|姓名' => 'requireIf:extract_type,0',
        'bank_code|银行卡号' => 'requireIf:extract_type,0|number',
        'extract_type|收款方式' => 'require',
        'extract_price|提现金额' => 'require|gt:0',
        'alipay_code|支付宝账户' => 'requireIf:extract_type,2',
    ];

}
