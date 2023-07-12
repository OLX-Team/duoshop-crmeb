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


namespace app\common\repositories\store;


use app\common\dao\store\PriceRuleDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\RelevanceRepository;
use think\facade\Db;

/**
 * @mixin PriceRuleDao
 */
class PriceRuleRepository extends BaseRepository
{
    public function __construct(PriceRuleDao $dao)
    {
        $this->dao = $dao;
    }

    public function lst(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->order('sort DESC, rule_id DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->with(['cate'])->select();
        return compact('count', 'list');
    }

    public function createRule(array $data)
    {
        $cateIds = (array)$data['cate_id'];
        unset($data['cate_id']);
        return Db::transaction(function () use ($cateIds, $data) {
            $data['is_default'] = count($cateIds) ? 0 : 1;
            $rule = $this->dao->create($data);
            $inserts = [];
            foreach ($cateIds as $id) {
                $inserts[] = [
                    'left_id' => $rule['rule_id'],
                    'right_id' => (int)$id,
                    'type' => RelevanceRepository::PRICE_RULE_CATEGORY
                ];
            }
            if (count($inserts)) {
                app()->make(RelevanceRepository::class)->insertAll($inserts);
            }

            return $rule;
        });
    }

    public function updateRule(int $id, array $data)
    {
        $cateIds = (array)$data['cate_id'];
        unset($data['cate_id']);
        $data['update_time'] = date('Y-m-d H:i:s');
        return Db::transaction(function () use ($id, $cateIds, $data) {
            $data['is_default'] = count($cateIds) ? 0 : 1;
            $this->dao->update($id, $data);
            $inserts = [];
            foreach ($cateIds as $cid) {
                $inserts[] = [
                    'left_id' => $id,
                    'right_id' => (int)$cid,
                    'type' => RelevanceRepository::PRICE_RULE_CATEGORY
                ];
            }
            app()->make(RelevanceRepository::class)->query([
                'left_id' => $id,
                'type' => RelevanceRepository::PRICE_RULE_CATEGORY
            ])->delete();
            if (count($inserts)) {
                app()->make(RelevanceRepository::class)->insertAll($inserts);
            }
        });
    }


}
