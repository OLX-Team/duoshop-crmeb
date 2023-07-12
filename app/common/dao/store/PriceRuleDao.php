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


namespace app\common\dao\store;


use app\common\dao\BaseDao;
use app\common\model\store\PriceRule;
use app\common\repositories\system\RelevanceRepository;

class PriceRuleDao extends BaseDao
{

    protected function getModel(): string
    {
        return PriceRule::class;
    }

    public function search(array $where)
    {
        return PriceRule::getDB()->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->whereLike('rule_name', "%{$where['keyword']}%");
        })->when(isset($where['is_show']) && $where['is_show'] !== '', function ($query) use ($where) {
            $query->where('is_show', $where['is_show']);
        })->when(isset($where['cate_id']) && $where['cate_id'] !== '', function ($query) use ($where) {
            $ids = app()->make(RelevanceRepository::class)->query([
                'type' => RelevanceRepository::PRICE_RULE_CATEGORY
            ])->where(function ($query) use ($where) {
                if (is_array($where['cate_id'])) {
                    $query->whereIn('right_id', $where['cate_id']);
                } else {
                    $query->where('right_id', (int)$where['cate_id']);
                }
            })->group('left_id')->column('left_id');
            $ids[] = -1;
            $query->where(function ($query) use ($ids) {
                $query->whereIn('rule_id', $ids)->whereOr('is_default', 1);
            });
        });
    }
}
