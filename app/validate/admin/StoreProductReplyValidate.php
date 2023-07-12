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

class StoreProductReplyValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'product_id|商品' => 'require|array|min:1',
        'nickname|用户昵称' => 'require|max:20',
        'comment|评论' => 'require|max:128',
        'product_score|商品分数' => 'require|integer|max:5',
        'service_score|服务分数' => 'require|integer|max:5',
        'postage_score|物流分数' => 'require|integer|max:5',
        'avatar|用户头像' => 'require',
        'pics|评价图片' => 'array|max:6',
    ];
}
