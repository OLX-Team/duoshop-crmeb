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
use app\common\model\store\service\StoreServiceReply;

/**
 * Class StoreServiceDao
 * @package app\common\dao\store\service
 * @author xaboy
 * @day 2020/5/29
 */
class StoreServiceReplyDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/5/29
     */
    protected function getModel(): string
    {
        return StoreServiceReply::class;
    }

    public function search(array $where)
    {
        return StoreServiceReply::getDB()->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', $where['mer_id']);
        })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('keyword', "%{$where['keyword']}%");
        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            $query->where('status', $where['status']);
        });
    }

    public function keywordByValidData($key, $merId)
    {
        return StoreServiceReply::getDB()->where(function ($query) use ($key) {
            $query->where('keyword', 'like',"%{$key}%")->whereFieldRaw('CONCAT(\',\',`keyword`,\',\')', 'LIKE', '%,' . $key . ',%', 'OR');
        })->where('status', 1)->where('mer_id', $merId)->find();
    }
}
