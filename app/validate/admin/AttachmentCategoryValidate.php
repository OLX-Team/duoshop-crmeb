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

class AttachmentCategoryValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'pid|选择分类' => 'require|integer',
        'attachment_category_name|分类名称' => 'require|max:16',
        'attachment_category_enname|分类目录' => 'require|alphaNum|max:16',
        'sort|排序' => 'require|integer'
    ];
}
