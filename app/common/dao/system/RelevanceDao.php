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
use app\common\model\system\Relevance;
use app\common\repositories\system\RelevanceRepository;

class RelevanceDao extends BaseDao
{

    protected function getModel(): string
    {
        return Relevance::class;
    }

    public function clear(int $id, $type, string $field)
    {
        if (is_string($type)) $type = [$type];
        return $this->getModel()::getDb()->where($field, $id)->whereIn('type', $type)->delete();
    }


    public function joinUser($where)
    {
        $query = Relevance::hasWhere('community',function($query) use($where){
            $query->where('status',1)->where('is_show',1)->where('is_del',0);
            $query->when(isset($where['is_type']) && $where['is_type'] !== '',function($query) use($where){
                $query->where('is_type',$where['is_type']);
            });
        });

        $query->where('left_id',$where['uid'])->where('type',RelevanceRepository::TYPE_COMMUNITY_START);
        return $query;
    }


}
