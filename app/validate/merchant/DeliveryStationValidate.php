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

class DeliveryStationValidate extends Validate
{
    protected $failException = true;

    protected $rule = [
        'station_name|门店名称' => 'require',
        'business|支持配送的物品品类' => 'require|number',
        'station_address|门店地址' => 'require',
        'city_name|所属城市' => 'require',
        'lng|门店经度' => 'require',
        'lat|门店纬度' => 'require',
        'contact_name|联系人姓名' => 'require',
        'phone|联系人电话' => 'require|mobile',
    ];

    public function sceneDada()
    {
        return $this->append('username','require|mobile')
            ->append('password','require');
    }

    public $message = [
        'username.mobile' => '达达账号必须是手机号',
        'username.require'=> '达达账号必须填写',
        'password' => '达达密码必须填写',
    ];
}
