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
use app\common\model\store\service\StoreServiceUser;
use app\common\repositories\system\ExtendRepository;
use app\common\repositories\user\UserRepository;

/**
 * Class StoreServiceDao
 * @package app\common\dao\store\service
 * @author xaboy
 * @day 2020/5/29
 */
class StoreServiceUserDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/5/29
     */
    protected function getModel(): string
    {
        return StoreServiceUser::class;
    }

    public function search(array $where)
    {
        return StoreServiceUser::getDB()->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
            $query->where('uid', $where['uid']);
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        })->when(isset($where['service_id']) && $where['service_id'] !== '', function ($query) use ($where) {
            $query->where('service_id', $where['service_id']);
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $uid = app()->make(UserRepository::class)->search(['keyword' => $where['keyword']])->limit(30)->column('uid');
            $uid = array_merge($uid, app()->make(ExtendRepository::class)->search([
                'keyword' => $where['keyword'],
                'type' => ExtendRepository::TYPE_SERVICE_USER_MARK,
                'mer_id' => $where['mer_id'] ?? null
            ])->column('link_id'), [0]);
            $query->whereIn('uid', array_unique($uid));
        });
    }

}
