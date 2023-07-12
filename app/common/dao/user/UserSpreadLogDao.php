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
use app\common\model\BaseModel;
use app\common\model\user\UserSpreadLog;

class UserSpreadLogDao extends BaseDao
{

    protected function getModel(): string
    {
        return UserSpreadLog::class;
    }

    public function add($uid, $spread_uid, $old_spread_uid, $admin_id = 0)
    {
        $this->create(compact('uid', 'spread_uid', 'admin_id', 'old_spread_uid'));
    }

    public function search($where)
    {
        return UserSpreadLog::getDB()->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->order('create_time DESC');
    }
}
