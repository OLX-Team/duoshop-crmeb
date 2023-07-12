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

class CommunityValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'image|图片' => 'require|array',
        'content|内容' => 'require|max:600',
        'spu_id|关联商品' => 'array',
    ];

}
