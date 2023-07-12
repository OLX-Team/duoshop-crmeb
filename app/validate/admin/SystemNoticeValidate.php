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

class SystemNoticeValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'type|商户类型' => 'require|in:1,2,3,4',
        'mer_id|商户' => 'requireIf:type,1|array',
        'is_trader|自营类型' => 'requireIf:type,2|in:0,1',
        'category_id|商户分类' => 'requireIf:type,3|array',
        'notice_title|公告标题' => 'require|max:100',
        'notice_content|公告内容' => 'require|max:800',
    ];
}
