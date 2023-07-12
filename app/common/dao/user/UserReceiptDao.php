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
use app\common\model\user\UserReceipt;
use think\facade\Db;

class UserReceiptDao extends BaseDao
{

    protected function getModel(): string
    {
        return UserReceipt::class;
    }

    /**
     * TODO 设置默认
     * @param int $id
     * @param int $uid
     * @author Qinii
     * @day 2020-10-16
     */
    public function isDefault(int $id, int $uid)
    {
        Db::transaction(function()use($id,$uid){
            $this->clearDefault($uid);
            $this->getModel()::getDB()->where($this->getPk(),$id)->update(['is_default' => 1]);
        });
    }

    /**
     * TODO 清楚其他默认
     * @param int $uid
     * @author Qinii
     * @day 2020-10-20
     */
    public function clearDefault(int $uid)
    {
        $this->getModel()::getDB()->where('uid',$uid)->update(['is_default' => 0]);
    }
}
