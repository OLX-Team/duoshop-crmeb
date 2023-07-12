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

namespace app\common\repositories\user;

use app\common\repositories\BaseRepository;
use app\common\dao\user\UserReceiptDao;

class UserReceiptRepository extends BaseRepository
{
    protected $dao;

    public function __construct(UserReceiptDao $dao)
    {
        $this->dao = $dao;
    }

    public function uidExists(int $id,int $uid)
    {
        return $this->dao->getWhereCount(['uid' => $uid,$this->dao->getPk() => $id,'is_del' => 0]);
    }

    public function getIsDefault(int $uid)
    {
        return $this->dao->getWhere(['uid' => $uid,'is_default' => 1]);
    }

    public function getList(array $where)
    {
        $where['is_del'] = 0;
        return $this->dao->getSearch($where)->order('is_default DESC , create_time ASC')->select();
    }

    public function detail(array $where)
    {
        return $this->dao->getSearch($where)->find();
    }
}
