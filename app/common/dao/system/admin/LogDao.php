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
use app\common\model\system\admin\Log;
use think\db\BaseQuery;

/**
 * Class LogDao
 * @package app\common\dao\system\admin
 * @author xaboy
 * @day 2020-04-16
 */
class LogDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return Log::class;
    }

    /**
     * @param array $where
     * @param $merId
     * @return BaseQuery
     * @author xaboy
     * @day 2020-04-16
     */
    public function search(array $where, $merId)
    {
        $query = Log::getDB()->where('mer_id', $merId)->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['date']);
        });
        if (isset($where['method']) && $where['method'] !== '') $query->where('method', $where['method']);
        if (isset($where['admin_id']) && $where['admin_id'] !== '') $query->where('admin_id', $where['admin_id']);
        if (isset($where['section_startTime']) && $where['section_startTime'] && isset($where['section_endTime']) && $where['section_endTime'])
            $query->where('create_time', '>', $where['section_startTime'])->where('create_time', '<', $where['section_endTime']);

        return $query;
    }
}
