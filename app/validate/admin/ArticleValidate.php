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

class ArticleValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'cid|选择分类' => 'require|integer',
        'title|标题' => 'require|max:32',
        'content|内容' => 'require',
        'author|作者' => 'require|max:32',
        'image_input|图片' => 'require|max:128',
        'is_hot|是否热门' => 'require|integer',
        'is_banner|是否为Banner' => 'require|integer'
    ];
}
