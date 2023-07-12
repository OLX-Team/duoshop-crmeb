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


namespace app\common\dao\store\service;


use app\common\dao\BaseDao;
use app\common\model\store\service\StoreService;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * Class StoreServiceDao
 * @package app\common\dao\store\service
 * @author xaboy
 * @day 2020/5/29
 */
class StoreServiceDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/5/29
     */
    protected function getModel(): string
    {
        return StoreService::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020/5/29
     */
    public function search(array $where)
    {
        return StoreService::getDB()->where('is_del', 0)->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('status', $where['status']);
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('nickname', "%{$where['keyword']}%");
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        })->when(isset($where['customer']) && $where['customer'] !== '', function ($query) use ($where) {
            $query->where('customer', $where['customer']);
        })->when(isset($where['is_verify']) && $where['is_verify'] !== '', function ($query) use ($where) {
            $query->where('is_verify', $where['is_verify']);
        })->when(isset($where['is_goods']) && $where['is_goods'] !== '', function ($query) use ($where) {
            $query->where('is_goods', $where['is_goods']);
        })->when(isset($where['is_open']) && $where['is_open'] !== '', function ($query) use ($where) {
            $query->where('is_open', $where['is_open']);
        })->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->when(isset($where['service_id']) && $where['service_id'] !== '', function ($query) use ($where) {
            $query->where('service_id', $where['service_id']);
        });
    }

    public function getService($uid, $merId = null)
    {
        return StoreService::getDB()->where('uid', $uid)->when(!is_null($merId), function ($query) use($merId) {
            $query->where('mer_id', $merId);
        })->where('is_del', 0)->find();
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
     * @param int $merId
     * @param int $id
     * @return bool
     * @author xaboy
     * @day 2020-05-13
     */
    public function merExists(int $merId, int $id)
    {
        return StoreService::getDB()->where($this->getPk(), $id)->where('mer_id', $merId)->where('is_del', 0)->count($this->getPk()) > 0;
    }

    /**
     * @param $merId
     * @param $uid
     * @param int|null $except
     * @return bool
     * @author xaboy
     * @day 2020/5/29
     */
    public function issetService($merId, $uid, ?int $except = null)
    {
        return StoreService::getDB()->where('uid', $uid)->when($except, function ($query, $except) {
                $query->where($this->getPk(), '<>', $except);
            })->where('mer_id', $merId)->where('is_del', 0)->count($this->getPk()) > 0;
    }

    /**
     * @param $uid
     * @param int|null $except
     * @return bool
     * @author xaboy
     * @day 2020/5/29
     */
    public function isBindService($uid, ?int $except = null)
    {
        return StoreService::getDB()->where('uid', $uid)->when($except, function ($query, $except) {
                $query->where($this->getPk(), '<>', $except);
            })->where('is_del', 0)->count($this->getPk()) > 0;
    }

    /**
     * @param int $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020/5/29
     */
    public function delete(int $id)
    {
        return StoreService::getDB()->where($this->getPk(), $id)->update(['is_del' => 1]);
    }

    /**
     * @param $merId
     * @return array|Model|null
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/5/29
     */
    public function getChatService($merId)
    {
        return StoreService::getDB()->where('mer_id', $merId)->where('is_del', 0)->where('status', 1)->order('status DESC, sort DESC, create_time ASC')
            ->hidden(['is_del'])->find();
    }

    public function getRandService($merId)
    {
        $services = StoreService::getDB()->where('mer_id', $merId)->where('is_open',1)->where('is_del', 0)->where('status', 1)->order('status DESC, sort DESC, create_time ASC')
            ->hidden(['is_del'])->select();
        if (!$services || !count($services)) return null;
        if (count($services) === 1) $services[0];
        return $services[max(random_int(0, count($services) - 1), 0)];
    }

    /**
     * @param $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/5/29
     */
    public function getValidServiceInfo($id)
    {
        return StoreService::getDB()->where('service_id', $id)->where('is_open',1)->where('status', 1)->where('is_del', 0)->hidden(['is_del'])->find();
    }

    /**
     * @param $merId
     * @return array
     * @author xaboy
     * @day 2020/7/1
     */
    public function getNoticeServiceInfo($merId)
    {
        return StoreService::getDB()->where('mer_id', $merId)->where('status', 1)->where('notify', 1)
            ->where('is_del', 0)->column('uid,phone,nickname');
    }

    public function close($id,$field)
    {
        $this->getModel()::getDB()->where($field, $id)->update(['status' => 0]);
    }

}
