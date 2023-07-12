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


namespace app\common\dao\system\menu;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\auth\Role;

/**
 * Class RoleDao
 * @package app\common\dao\system\menu
 * @author xaboy
 * @day 2020-04-18
 */
class RoleDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return Role::class;
    }

    /**
     * @param $merId
     * @param array $where
     * @return BaseModel|Role
     * @author xaboy
     * @day 2020-04-18
     */
    public function search($merId, array $where = [])
    {
        $roleModel = Role::getInstance();

        if (isset($where['role_name'])) {
            $roleModel = $roleModel->whereLike('role_name', '%' . $where['role_name'] . '%');
        }

        if (isset($where['status'])) {
            $roleModel = $roleModel->where('status', intval($where['status']));
        }

        if (isset($where['role_ids']) && $where['role_ids'] !== '') {
            $roleModel = $roleModel->whereIn('role_id', $where['role_ids']);
        }

        return $roleModel->where('mer_id', $merId);
    }

    /**
     * @param int $merId
     * @return array
     * @author xaboy
     * @day 2020-04-18
     */
    public function getAllOptions(int $merId)
    {
        return Role::getDB()->where('status', 1)->where('mer_id', $merId)->column('role_name', 'role_id');
    }

    /**
     * @param $merId
     * @param array $ids
     * @return array
     * @author xaboy
     * @day 2020-04-18
     */
    public function idsByRules($merId, array $ids)
    {
        $rules = Role::getDB()->where('status', 1)->where('mer_id', $merId)->whereIn($this->getPk(), $ids)->column('rules');
        $data = [];
        foreach ($rules as $rule) {
            $data = array_merge(explode(',', $rule), $data);
        }
        return array_unique($data);
    }

    /**
     * @param int $merId
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020-04-18
     */
    public function merExists(int $merId, int $id)
    {
        return Role::getDB()->where($this->getPk(), $id)->where('mer_id', $merId)->count() > 0;
    }
}

