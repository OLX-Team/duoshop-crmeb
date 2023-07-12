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

namespace app\common\dao\store\parameter;

use app\common\dao\BaseDao;
use app\common\model\store\parameter\Parameter;

class ParameterDao extends BaseDao
{

    protected function getModel(): string
    {
        return Parameter::class;
    }


    public function search(array $where)
    {
    }

    public function clear(int $id, string $field)
    {
        $this->getModel()::getDB()->where($field, $id)->delete();
    }
}
