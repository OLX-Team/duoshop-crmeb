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


namespace app\common\repositories\store\service;


use app\common\dao\store\service\StoreServiceLogDao;
use app\common\model\store\service\StoreServiceLog;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\order\StoreRefundOrderRepository;
use app\common\repositories\store\product\ProductGroupRepository;
use app\common\repositories\store\product\ProductPresellRepository;
use app\common\repositories\store\product\ProductRepository;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 * Class StoreServiceLogRepository
 * @package app\common\repositories\store\service
 * @author xaboy
 * @day 2020/5/29
 * @mixin StoreServiceLogDao
 */
class StoreServiceLogRepository extends BaseRepository
{
    /**
     * StoreServiceLogRepository constructor.
     * @param StoreServiceLogDao $dao
     */
    public function __construct(StoreServiceLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $merId
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/15
     */
    public function userList($merId, $uid, $page, $limit)
    {
        $query = $this->search(['mer_id' => $merId, 'uid' => $uid])->order('service_log_id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->with(['user', 'service'])->select()->append(['send_time', 'send_date']);
        if ($page == 1) {
            $this->dao->userRead($merId, $uid);
            app()->make(StoreServiceUserRepository::class)->read($merId, $uid);
        }
        $list = array_reverse($this->getSendDataList($list)->toArray());
        return compact('count', 'list');
    }

    /**
     * @param $merId
     * @param $toUid
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/15
     */
    public function merList($merId, $toUid, $uid, $page, $limit)
    {
        $service = app()->make(StoreServiceRepository::class)->getService($uid, $merId);
        if (!$service || !$service['status'])
            throw new ValidateException('没有权限');
        return $this->serviceList($merId, $service->service_id, $toUid, $page, $limit);
    }

    public function serviceList($merId, $service_id, $toUid, $page, $limit, $last_id = '')
    {
        $query = $this->search(['mer_id' => $merId, 'uid' => $toUid, 'last_id' => $last_id])->order('service_log_id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->with(['user', 'service'])->select()->append(['send_time', 'send_date']);
        if ($page == 1) {
            $this->dao->serviceRead($merId, $toUid, $service_id);
            app()->make(StoreServiceUserRepository::class)->read($merId, $toUid, true);
        }
        $list = array_reverse($this->getSendDataList($list)->toArray());
        return compact('count', 'list');
    }

    /**
     * @param $merId
     * @param $uid
     * @param $type
     * @param $msn
     * @author xaboy
     * @day 2020/6/13
     */
    public function checkMsn($merId, $uid, $type, $msn)
    {
        if ($type == 4 && !app()->make(ProductRepository::class)->merExists($merId, $msn))
            throw new ValidateException('商品不存在');
        else if ($type == 5 && !app()->make(StoreOrderRepository::class)->existsWhere(['uid' => $uid, 'mer_id' => $merId, 'order_id' => $msn]))
            throw new ValidateException('订单不存在');
        else if ($type == 6 && !app()->make(StoreRefundOrderRepository::class)->existsWhere(['uid' => $uid, 'mer_id' => $merId, 'refund_order_id' => $msn]))
            throw new ValidateException('退款单不存在');
        else if ($type == 7 && !app()->make(ProductPresellRepository::class)->existsWhere(['product_presell_id' => $msn, 'mer_id' => $merId]))
            throw new ValidateException('商品不存在');
        else if ($type == 8 && !app()->make(ProductGroupRepository::class)->existsWhere(['product_group_id' => $msn, 'mer_id' => $merId]))
            throw new ValidateException('商品不存在');
    }

    /**
     * @param StoreServiceLog $log
     * @return StoreServiceLog
     * @author xaboy
     * @day 2020/6/15
     */
    public function getSendData(StoreServiceLog $log)
    {
        if ($log->msn_type == 4)
            $log->product;
        else if ($log->msn_type == 5)
            $log->orderInfo;
        else if ($log->msn_type == 6)
            $log->refundOrder;
        else if ($log->msn_type == 7)
            $log->presell;
        else if ($log->msn_type == 8)
            $log->productGroup;
        return $log;
    }

    public function getSendDataList($list)
    {
        $cache = [];
        foreach ($list as $log) {
            if (!in_array($log->msn_type, [4, 5, 6, 7, 8])) continue;
            $key = $log->msn_type . $log->msn;
            if (isset($cache[$key])) {
                if ($log->msn_type == 4)
                    $log->set('product', $cache[$key]);
                else if ($log->msn_type == 5)
                    $log->set('orderInfo', $cache[$key]);
                else if ($log->msn_type == 6)
                    $log->set('refundOrder', $cache[$key]);
                else if ($log->msn_type == 8)
                    $log->set('productGroup', $cache[$key]);
                else
                    $log->set('presell', $cache[$key]);
            } else {
                if ($log->msn_type == 4)
                    $cache[$key] = $log->product;
                else if ($log->msn_type == 5)
                    $cache[$key] = $log->orderInfo;
                else if ($log->msn_type == 6)
                    $cache[$key] = $log->refundOrder;
                else if ($log->msn_type == 8)
                    $cache[$key] = $log->productGroup;
                else
                    $cache[$key] = $log->presell;
            }
        }
        return $list;
    }

    /**
     * @param $uid
     * @param bool $isService
     * @author xaboy
     * @day 2020/6/15
     */
    public function getChat($uid, $isService = false)
    {
        $key = ($isService ? 's_chat' : 'u_chat') . $uid;
        return Cache::get($key);
    }

    /**
     * TODO 获取某个客服的用户列表
     * @param $service_id
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-18
     */
    public function getServiceUserList($service_id, $page, $limit)
    {
        $query = $this->dao->getUserListQuery($service_id)->with(['user'])->group('uid');
        $count = $query->count();

        $list = $query->setOption('field', [])->field('uid,mer_id,create_time,type')
            ->page($page, $limit)
            ->select();

        return compact('count', 'list');
    }

    /**
     * TODO 获取商户的聊天用户列表
     * @param $merId
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-19
     */
    public function getMerchantUserList($merId, $page, $limit)
    {
        $query = $this->dao->getMerchantUserList($merId)->with(['user'])->group('uid');
        $count = $query->count();
        $list = $query->setOption('field', [])->field('uid,mer_id,create_time,type')->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * TODO
     * @param $merId
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-19
     */
    public function getUserMsn(int $uid, $page, $limit, ?int $merId = null, ?int $serviceId = null)
    {
        $where['uid'] = $uid;
        if ($merId) $where['mer_id'] = $merId;
        if ($serviceId) $where['service_id'] = $serviceId;
        $query = $this->search($where)->order('service_log_id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->with(['user', 'service'])->select();
        return compact('count', 'list');
    }
}
