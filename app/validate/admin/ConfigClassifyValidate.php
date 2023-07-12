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

class ConfigClassifyValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'pid|上级分类' => 'require|integer',
        'classify_name|配置分类名称' => 'require|max:20',
        'classify_key|配置分类key' => 'require|max:20',
        'info|配置分类说明' => 'max:30',
        'status|显示状态' => 'require|integer|in:0,1',
        'sort|排序' => 'require|integer',
        'icon|图标' => 'max:15'
    ];
}
