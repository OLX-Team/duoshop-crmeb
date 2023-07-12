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


namespace app\common\dao\user;


use app\common\dao\BaseDao;
use app\common\model\user\LabelRule;

class LabelRuleDao extends BaseDao
{

    protected function getModel(): string
    {
        return LabelRule::class;
    }

    public function search(array $where)
    {
        return LabelRule::hasWhere('label')->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('UserLabel.label_name', "%{$where['keyword']}%");
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('LabelRule.type', intval($where['type']));
        })->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
            $query->where('LabelRule.mer_id', intval($where['mer_id']));
        });
    }
}
