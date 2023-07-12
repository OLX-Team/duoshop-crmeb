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

class UserValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'real_name|真实姓名' => 'max:25',
        'phone|手机号' => 'isPhone',
        'birthday|生日' => 'dateFormat:Y-m-d',
        'card_id|身份证' => 'length:18',
        'addres|用户地址' => 'max:64',
        'mark|备注' => 'max:200',
        'group_id|分组' => 'integer',
        'label_id|标签' => 'array',
        'is_promoter|推广人' => 'in:0,1',
        'status|推广人' => 'in:0,1'
    ];

    protected function isPhone($val)
    {
        if (!preg_match('/^1[3456789]{1}\d{9}$/', $val))
            return '请输入正确的手机号';
        else
            return true;
    }

    protected  function  sceneCreate()
    {
        return $this->append('account','require|unique:user,account|max:16|min:5')
            ->append('pwd','require')
            ->append('repwd','require|confirm:pwd')
            ->append('sex','in:0,1,2');
    }

    protected $message = [
        'account.require' => '账号必填',
        'account.unique' => '账号已经存在',
        'account.min' => '账号最小长度为5',
        'account.max' => '账号最大长度为16',
        'pwd.require' => '密码必填',
        'repwd.require' => '确认密码必填',
        'repwd.confirm' => '密码不一致',
    ];
}
