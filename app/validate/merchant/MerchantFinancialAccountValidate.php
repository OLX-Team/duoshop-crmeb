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


namespace app\validate\merchant;


use think\Validate;

class MerchantFinancialAccountValidate extends Validate
{
    protected $failException = true;
    /*
    {
        "financial_type":2,
        "name":"王二小",
        "idcard":"",
        "wechat":"1",
        "wechat_code":"456461516"
    }
    {
        "financial_type":3,
        "name":"王二小",
        "idcard":"",
        "alipay":"1",
        "alipay_code":"456461516"
    }
    */
    protected $rule = [
        'financial_type|转账类型' => 'require|in:1,2,3',
        'name|姓名' => 'require|chs',
        'bank|开户银行' => 'requireIf:financial_type,1',
        'bank_code|银行卡号' => 'requireIf:financial_type,1',
        //'idcard|身份证号' => 'requireIf:financial_type,2,3|idCard',
        'wechat|微信号' => 'requireIf:financial_type,2',
        'wechat_code|微信收款二维码' => 'requireIf:financial_type,2',
        'alipay|支付宝账号' => 'requireIf:financial_type,3',
        'alipay_code|收款二维码' => 'requireIf:financial_type,3',
    ];
}

