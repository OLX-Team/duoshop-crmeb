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


use app\common\dao\store\service\StoreServiceUserDao;
use app\common\model\store\service\StoreServiceLog;
use app\common\repositories\BaseRepository;
use think\exception\ValidateException;

/**
 * Class StoreServiceRepository
 * @package app\common\repositories\store\service
 * @author xaboy
 * @day 2020/5/29
 * @mixin StoreServiceUserDao
 */
class StoreServiceUserRepository extends BaseRepository
{
    /**
     * StoreServiceRepository constructor.
     * @param StoreServiceUserDao $dao
     */
    public function __construct(StoreServiceUserDao $dao)
    {
        $this->dao = $dao;
    }

    public function updateInfo(StoreServiceLog $log, $isService)
    {
        $serviceUser = $this->dao->getWhere(['service_id' => $log->service_id, 'uid' => $log->uid, 'mer_id' => $log->mer_id]);
        if (!$serviceUser) {
            $serviceUser = $this->dao->create([
                'service_id' => $log->service_id,
                'uid' => $log->uid,
                'mer_id' => $log->mer_id,
                'service_unread' => $isService ? 1 : 0,
                'user_unread' => $isService ? 0 : 1,
                'is_online' => 1,
                'last_log_id' => $log->service_log_id,
                'last_time' => date('Y-m-d H:i:s')
            ]);
        } else {
            $isService ? $serviceUser->service_unread++ : $serviceUser->user_unread++;
            $serviceUser->last_log_id = $log->service_log_id;
            $serviceUser->last_time = date('Y-m-d H:i:s');
            $serviceUser->is_online = 1;
            $serviceUser->save();
        }

        return $serviceUser;
    }

    public function read($merId, $uid, $isService = null)
    {
        $field = $isService ? 'service_unread' : 'user_unread';
        $this->dao->search([
            'mer_id' => $merId,
            'uid' => $uid,
        ])->update([
            $field => 0
        ]);
    }

    public function userMerchantList($uid, $page, $limit)
    {
        $query = $this->dao->search(['uid' => $uid])->group('mer_id')->order('last_time DESC');
        $count = $query->count();
        $list = $query->with(['merchant' => function ($query) {
            $query->field('mer_id,mer_avatar,mer_name');
        }, 'last'])->page($page, $limit)->setOption('field', [])->field('*,max(last_log_id) as last_log_id,sum(user_unread) as num')->select()->toArray();

        $config = systemConfig(['site_logo', 'site_name']);

        foreach ($list as &$item) {
            if ($item['mer_id'] == 0) {
                $item['merchant'] = [
                    'mer_avatar' => $config['site_logo'],
                    'mer_name' => $config['site_name'],
                    'mer_id' => 0,
                ];
            }
        }
        unset($item);

        return compact('count', 'list');
    }

    public function merUserList($merId, $uid, $page, $limit)
    {
        $service = app()->make(StoreServiceRepository::class)->getService($uid, $merId);
        if (!$service)
            throw new ValidateException('没有权限');
        if (!$service['status'])
            throw new ValidateException('客服已离线，清开启客服状态');
        return $this->serviceUserList(['service_id' => $service->service_id], $merId, $page, $limit);

    }

    public function serviceUserList($where, $merId, $page, $limit)
    {
        $query = $this->dao->search($where)->group('uid')->order('last_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->with([
            'user' => function ($query) {
                $query->field('uid,avatar,nickname,user_type,sex,is_promoter,phone,now_money,phone,birthday,spread_uid')->with([
                    'spread' => function ($query) {
                        $query->field('uid,avatar,nickname,cancel_time');
                    }
                ]);
            },
            'mark' => function ($query) use ($merId) {
                $query->where('mer_id', $merId)->bind(['mark' => 'extend_value']);
            },
            'last'
        ])->setOption('field', [])->field('*,max(last_log_id) as last_log_id,sum(service_unread) as num')->select()->toArray();
        if (count($list) && is_null($list[0]['service_user_id'])) {
            $list = [];
        }
        return compact('count', 'list');
    }

    public function online($uid, $online)
    {
        return $this->dao->search(['uid' => $uid])->update(['is_online' => $online]);
    }

    public function onlineDown()
    {
        return $this->dao->search([])->where(['is_online' => 1])->update(['is_online' => 0]);
    }

}
