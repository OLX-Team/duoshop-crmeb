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
use app\common\model\store\service\StoreServiceLog;
use think\db\BaseQuery;

/**
 * Class StoreServiceLogDao
 * @package app\common\dao\store\service
 * @author xaboy
 * @day 2020/5/29
 */
class StoreServiceLogDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/5/29
     */
    protected function getModel(): string
    {
        return StoreServiceLog::class;
    }

    /**
     * @param $merId
     * @param $uid
     * @return int
     * @throws \think\db\exception\DbException
     * @author xaboy
     * @day 2020/6/16
     */
    public function userRead($merId, $uid)
    {
        return StoreServiceLog::getDB()->where('mer_id', $merId)->where('uid', $uid)->where('type', '<>', 1)->update(['type' => 1]);
    }

    /**
     * @param $uid
     * @param $merId
     * @return bool
     * @author xaboy
     * @day 2020/6/16
     */
    public function issetLog($uid, $merId)
    {
        return StoreServiceLog::getDB()->where('mer_id', $merId)->where('uid', $uid)->where('send_type', 0)->limit(1)->count() > 0;
    }

    /**
     * @param $merId
     * @param $uid
     * @param $serviceId
     * @return int
     * @throws \think\db\exception\DbException
     * @author xaboy
     * @day 2020/10/15
     */
    public function serviceRead($merId, $uid, $serviceId)
    {
        return StoreServiceLog::getDB()->where('mer_id', $merId)->where('uid', $uid)->where('service_id', $serviceId)->where('service_type', '<>', 1)->update(['service_type' => 1]);
    }

    /**
     * @param array $where
     * @return \think\db\BaseQuery
     * @author xaboy
     * @day 2020/6/16
     */
    public function search(array $where)
    {
        return StoreServiceLog::getDB()->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        })->when(isset($where['last_id']) && $where['last_id'] !== '', function ($query) use ($where) {
            $query->where('service_log_id', '<', $where['last_id']);
        })->when(isset($where['service_id']) && $where['service_id'] !== '', function ($query) use ($where) {
            $query->where('service_id', $where['service_id']);
        });
    }

    /**
     * @param $merId
     * @param $uid
     * @return mixed
     * @author xaboy
     * @day 2020/5/29
     */
    public function getLastServiceId($merId, $uid)
    {
        return StoreServiceLog::getDB()->where('mer_id', $merId)->order('service_log_id DESC')->where('uid', $uid)->value('service_id');
    }

    /**
     * @param $uid
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/16
     */
    public function getMerchantListQuery($uid)
    {
        return StoreServiceLog::getDB()->where('uid', $uid)->group('mer_id');
    }

    /**
     * TODO 客服的所有用户
     * @param $serviceId
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/16
     */
    public function getUserListQuery($serviceId)
    {
        return StoreServiceLog::getDB()->where('service_id', $serviceId);
    }

    /**
     * TODO 商户的所有用户
     * @param $merId
     * @return mixed
     * @author Qinii
     * @day 2020-06-19
     */
    public function getMerchantUserList($merId)
    {
        return StoreServiceLog::getDB()->where('mer_id', $merId);
    }

    /**
     * @param $merId
     * @param $uid
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/19
     */
    public function getLastLog($merId, $uid)
    {
        return StoreServiceLog::getDB()->where('mer_id', $merId)->where('uid', $uid)->order('service_log_id DESC')->find();
    }

    /**
     * @param $merId
     * @param $uid
     * @param $sendType
     * @return int
     * @author xaboy
     * @day 2020/6/19
     */
    public function getUnReadNum($merId, $uid, $sendType)
    {
        return StoreServiceLog::getDB()->where('uid', $uid)->where('mer_id', $merId)->where('send_type', $sendType)->where($sendType ? 'type' : 'service_type', 0)->count();
    }

    /**
     * @param $uid
     * @return int
     * @author xaboy
     * @day 2020/6/19
     */
    public function totalUnReadNum($uid)
    {
        return StoreServiceLog::getDB()->where('uid', $uid)->where('send_type', 1)->where('type', 0)->count();
    }
}
