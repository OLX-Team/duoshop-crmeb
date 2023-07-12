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


namespace app\validate\admin;


use think\Validate;

class ConfigValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'config_classify_id|配置分类' => 'require|integer',
        'config_name|配置名称' => 'require|max:64',
        'config_key|配置key' => 'require|max:64',
        'config_type|配置类型' => 'require|max:15',
        'config_rule|配置规则' => 'max:250',
        'required|必填状态' => 'require|in:0,1',
        'info|配置说明' => 'max:255',
        'sort|排序' => 'require|integer',
        'status|状态' => 'require|in:0,1',
        'user_type|后台类型' => 'require|in:0,1'
    ];
}
