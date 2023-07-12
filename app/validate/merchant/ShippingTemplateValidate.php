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

class ShippingTemplateValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'name|名称' => 'require|max:32',
        'type|计费方式' => 'require|in:0,1,2',
        'appoint|指定包邮状态' => 'require|in:0,1',
        'region|配送区域信息' => 'Array|require|min:1|region',
        'undelivery|区域不配送状态' => 'require|in:0,1,2',
        'free|包邮信息' => 'requireIf:appoint,1|Array|free',
        'undelives|不配送区域信息'=>'requireIf:undelivery,1|Array|undelive',
    ];

    protected function region($value,$rule,$data)
    {
        foreach ($value as $k => $v){
            if ($k != 0 && empty($v['city_id']))
                return '配送城市信息不能为空';
            if (!is_numeric($v['first']) || $v['first'] < 0)
                return '首件条件不能小0';
            if (!is_numeric($v['first_price']) || $v['first_price'] < 0)
                return '首件金额不能小于0';
            if (!is_numeric($v['continue']) || ($v['continue'] < 0))
                return '续件必须为不小于零的整数';
            if (!is_numeric($v['continue_price']) || $v['continue_price'] < 0 )
                return '追加金额为不小于零的数';
        }
        return true;
    }
    protected function free($value,$rule,$data)
    {
        if(!$data['appoint']) return true;
        foreach ($value as $v){
            if (empty($v['city_id']))
                return '包邮城市信息不能为空';
            if (!is_int($v['number']) || $v['number'] < 0)
                return '包邮条件为不小于零的整数';
            if (!is_numeric($v['price']) ||$v['price'] < 0)
                return '包邮金额必须为不小于零的数字';
        }
        return true;
    }

    protected function undelive($value,$rule,$data)
    {
        if($data['undelivery'] == 1){
            if (empty($value['city_id']))
                return '不配送城市信息不能为空';
        }
        return true;
    }
}
