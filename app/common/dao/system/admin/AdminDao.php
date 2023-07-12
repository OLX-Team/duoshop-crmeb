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


namespace app\common\dao\system\admin;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\admin\Admin;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

class AdminDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return Admin::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-04-09
     */
    public function search(array $where = [], $is_del = 0,$level = true)
    {
        $query = Admin::getDB();
        if($level) $query->where('level', '<>', 0);
        $query->when($is_del !== null, function ($query) use ($is_del) {
            $query->where('is_del', $is_del);
        })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['date']);
        });
        if (isset($where['keyword']) && $where['keyword'] !== '') {
            $query = $query->whereLike('real_name|account', '%' . $where['keyword'] . '%');
        }
        if (isset($where['status']) && $where['status'] !== '') {
            $query = $query->where('status', intval($where['status']));
        }
        return $query;
    }

    public function exists(int $id)
    {
        $query = ($this->getModel())::getDB()->where($this->getPk(), $id)->where('is_del', 0);
        return $query->count() > 0;
    }


    /**
     * @param int $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-09
     */
    public function get( $id)
    {
        return Admin::getInstance()->where('is_del', 0)->find($id);
    }


    /**
     * @param $field
     * @param $value
     * @param int|null $except
     * @return bool
     * @author xaboy
     * @day 2020-03-30
     */
    public function fieldExists($field, $value, ?int $except = null): bool
    {
        $query = ($this->getModel())::getDB()->where($field, $value)->where('is_del', 0);
        if (!is_null($except)) $query->where($this->getPk(), '<>', $except);
        return $query->count() > 0;
    }

    /**
     * @param string $account
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-09
     */
    public function accountByAdmin(string $account)
    {
        return Admin::getInstance()->where('account', $account)
            ->where('is_del', 0)
            ->field(['account', 'pwd', 'real_name', 'login_count', 'admin_id', 'status'])
            ->find();
    }
}

