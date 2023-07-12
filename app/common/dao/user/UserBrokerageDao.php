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
use app\common\model\BaseModel;
use app\common\model\user\UserBrokerage;

class UserBrokerageDao extends BaseDao
{

    protected function getModel(): string
    {
        return UserBrokerage::class;
    }

    public function search(array $where)
    {
        return UserBrokerage::getDB()->when(isset($where['brokerage_name']) && $where['brokerage_name'] !== '', function ($query) use ($where) {
            $query->whereLike('brokerage_name', "%{$where['brokerage_name']}%");
        })->when(isset($where['brokerage_level']) && $where['brokerage_level'] !== '', function ($query) use ($where) {
            $query->where('brokerage_level', $where['brokerage_level']);
        })->when(isset($where['next_level']) && $where['next_level'] !== '', function ($query) use ($where) {
            $query->where('brokerage_level', '>', $where['next_level']);
        })->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('type', $where['type']);
        });
    }

    public function fieldExists($field, $value, ?int $except = null, int $type = 0): bool
    {
        $query = ($this->getModel())::getDB()->where('type',$type)->where($field, $value);
        if (!is_null($except)) $query->where($this->getPk(), '<>', $except);
        return $query->count() > 0;
    }
}
