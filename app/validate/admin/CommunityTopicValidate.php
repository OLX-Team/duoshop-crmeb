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

class CommunityTopicValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'category_id|选择分类' => 'require|integer',
        'topic_name|输入话题' => 'require|max:20',
        'is_hot|推荐' => 'in:0,1',
        'status|状态' => 'require|in:0,1',
    ];
}
