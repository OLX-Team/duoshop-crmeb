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


namespace app\common\dao\system;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\Extend;

/**
 * Class ExtendDao
 * @package app\common\dao\system
 * @author xaboy
 * @day 2020-04-24
 */
class ExtendDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return Extend::class;
    }

    public function search(array $where)
    {
        return Extend::getDB()->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('extend_value', "%{$where['keyword']}%");
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('extend_type', $where['type']);
        })->when(isset($where['link_id']) && $where['link_id'] !== '', function ($query) use ($where) {
            $query->where('link_id', (int)$where['link_id']);
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('mer_id', (int)$where['mer_id']);
        });
    }

}
