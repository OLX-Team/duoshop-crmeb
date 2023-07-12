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

class WechatNewsValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'data' => 'array|checkArray',
//        'title|标题' => 'require',
//        'author|作者' => 'require',
//        'synopsis|摘要' => 'require',
//        'image_input|图片' => 'require',
//        'content|内容' => 'require',
    ];



    protected function checkArray($value,$rule,$data = [])
    {
        foreach ($value as $v) {
            if(empty($v['title']))
                return '标题不能为空';
            if(empty($v['author']))
                return '作者不能为空';
            if(empty($v['synopsis']))
                return '摘要不能为空';
            if(empty($v['image_input']))
                return '图片不能为空';
            if(empty($v['content']))
                return '内容不能为空';
        }
        return true;
    }


}
