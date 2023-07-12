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

class MenuValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'pid|选择父级分类' => 'require|integer|max:5',
        'icon|图标' => 'max:16',
        'menu_name|按钮名' => 'require|max:32',
        'route|菜单地址' => 'require|max:64',
        'sort|排序' => 'integer|max:3',
        'is_show|是否显示' => 'integer|in:0,1',
    ];

    public function isAuth()
    {
        unset($this->rule['icon|图标']);
        unset($this->rule['is_show|是否显示']);
        return $this;
    }
}
