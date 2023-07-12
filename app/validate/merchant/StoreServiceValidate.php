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


namespace app\validate\merchant;


use think\Validate;

class StoreServiceValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'uid|客服' => 'require|array',
        'uid.id|客服' => 'require|integer',
        'nickname|客服名称' => 'require|max:12',
        'avatar|客服头像' => 'max:250',
        'account|客服账号' => 'require|min:5|max:16',
        'pwd|客服密码' => 'require|min:6|max:16',
        'confirm_pwd|确认密码' => 'require|min:6|max:16',
        'is_open|状态' => 'require|in:0,1',
        'status|状态' => 'require|in:0,1',
        'is_verify|核销状态' => 'require|in:0,1',
        'is_goods|商品管理状态' => 'require|in:0,1',
        'notify|订单通知状态' => 'require|in:0,1',
        'sort|排序' => 'require|integer',
        'customer|展示统计管理状态' => 'require|in:0,1',
    ];

    protected $message = [
        'account.min' => '客服账号长度不能小于5个字符',
        'uid.require' => '请选择一个用户绑定为客服',
        'uid.id.require' => '用户ID不能为空'
    ];

    public function update()
    {
        $this->rule['pwd|客服密码'] = 'min:6|max:16';
        $this->rule['confirm_pwd|确认密码'] = 'min:6|max:16';
        return $this;
    }
}
