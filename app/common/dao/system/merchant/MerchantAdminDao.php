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


namespace app\common\dao\system\merchant;


use app\common\dao\BaseDao;
use app\common\model\system\merchant\MerchantAdmin;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Model;

/**
 * Class MerchantAdminDao
 * @package app\common\dao\system\merchant
 * @author xaboy
 * @day 2020-04-17
 */
class MerchantAdminDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020-04-16
     */
    protected function getModel(): string
    {
        return MerchantAdmin::class;
    }

    /**
     * @param int $merId
     * @param array $where
     * @param int|null $level
     * @return BaseQuery
     * @author xaboy
     * @day 2020-04-18
     */
    public function search(int $merId, array $where = [], ?int $level = null)
    {
        $query = MerchantAdmin::getDB()->where('is_del', 0)->where('mer_id', $merId)
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date']);
            });

        if (!is_null($level)) $query->where('level', $level);

        if (isset($where['keyword']) && $where['keyword'] !== '') {
            $query = $query->whereLike('real_name|account', '%' . $where['keyword'] . '%');
        }

        if (isset($where['status']) && $where['status'] !== '') {
            $query = $query->where('status', intval($where['status']));
        }

        return $query;
    }

    /**
     * @param int $merId
     * @return string
     * @author xaboy
     * @day 2020-04-16
     */
    public function merIdByAccount(int $merId): string
    {
        return MerchantAdmin::getDB()->where('mer_id', $merId)->where('level', 0)->value('account');
    }

    /**
     * @param int $merId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/7/7
     */
    public function merIdByAdmin(int $merId)
    {
        return MerchantAdmin::getDB()->where('mer_id', $merId)->where('level', 0)->find();
    }

    /**
     * @param string $account
     * @param int $merId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-20
     */
    public function accountByAdmin(string $account, int $merId)
    {
        return MerchantAdmin::getInstance()->where('account', $account)
            ->where('is_del', 0)->where('mer_id', $merId)
            ->field(['account', 'pwd', 'real_name', 'login_count', 'merchant_admin_id', 'status', 'mer_id'])
            ->find();
    }

    /**
     * @param string $account
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-20
     */
    public function accountByTopAdmin(string $account)
    {
        return MerchantAdmin::getInstance()->where('account', $account)
            ->where('is_del', 0)->where('level', 0)
            ->field(['account', 'pwd', 'real_name', 'login_count', 'merchant_admin_id', 'status', 'mer_id'])
            ->find();
    }

    /**
     * @param string $account
     * @return mixed
     * @author xaboy
     * @day 2020-04-20
     */
    public function accountByMerchantId(string $account)
    {
        return MerchantAdmin::getInstance()->where('account', $account)->value('mer_id');
    }


    /**
     * @param int $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-17
     */
    public function get( $id)
    {
        return MerchantAdmin::getInstance()->where('is_del', 0)->find($id);
    }

    /**
     * @param int $id
     * @param int $merId
     * @param int|null $level
     * @return bool
     * @author xaboy
     * @day 2020-04-18
     */
    public function exists(int $id, int $merId = 0, ?int $level = null)
    {
        $query = MerchantAdmin::getDB()->where($this->getPk(), $id)->where('is_del', 0);
        if ($merId) $query->where('mer_id', $merId);
        if (!is_null($level)) $query->where('level', $level);
        return $query->count() > 0;
    }

    /**
     * @param int $merId
     * @param $field
     * @param $value
     * @param int|null $except
     * @return bool
     * @author xaboy
     * @day 2020-04-18
     */
    public function merFieldExists(int $merId, $field, $value, ?int $except = null): bool
    {
        $query = MerchantAdmin::getDB()->where($field, $value)->where('mer_id', $merId);
        if (!is_null($except)) $query->where($this->getPk(), '<>', $except);
        return $query->count() > 0;
    }

    /**
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020-04-18
     */
    public function topExists(int $id)
    {
        $query = MerchantAdmin::getDB()->where($this->getPk(), $id)->where('is_del', 0)->where('level', 0);
        return $query->count() > 0;
    }

    /**
     * @param int $merId
     * @return mixed
     * @author xaboy
     * @day 2020-04-17
     */
    public function merchantIdByTopAdminId(int $merId)
    {
        return MerchantAdmin::getDB()->where('mer_id', $merId)->where('is_del', 0)->where('level', 0)->value('merchant_admin_id');
    }

    public function deleteMer($merId)
    {
        MerchantAdmin::getDB()->where('mer_id', $merId)->update(['account' => Db::raw('CONCAT(`account`,\'$del\')')]);
    }
}
