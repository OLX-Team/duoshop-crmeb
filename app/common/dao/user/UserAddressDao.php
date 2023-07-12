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


namespace app\common\dao\user;


use app\common\dao\BaseDao;
use app\common\model\user\UserAddress as model;

class UserAddressDao extends BaseDao
{


    /**
     * @return string
     * @author Qinii
     */
    protected function getModel(): string
    {
        return model::class;
    }


    public function userFieldExists($field, $value,$uid): bool
    {
        return (($this->getModel()::getDB())->where('uid',$uid)->where($field,$value)->count()) > 0;
    }

    public function changeDefault(int $uid)
    {
        return ($this->getModel()::getDB())->where('uid',$uid)->update(['is_default' => 0]);
    }

    public function getAll(int $uid)
    {
        return (($this->getModel()::getDB())->where('uid',$uid));
    }
}
