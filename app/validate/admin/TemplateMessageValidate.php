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

class TemplateMessageValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'tempkey|模板编号' => 'require',
        'name|模板名' => 'require',
        'tempid|模板ID' => 'require',
        'content|回复内容' => 'require',
        'status|状态' => 'require',
    ];
}
