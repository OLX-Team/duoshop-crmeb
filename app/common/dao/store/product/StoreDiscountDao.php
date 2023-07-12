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

namespace app\common\dao\store\product;

use app\common\dao\BaseDao;
use app\common\model\store\product\StoreDiscounts;

class StoreDiscountDao extends BaseDao
{
    protected function getModel(): string
    {
        return StoreDiscounts::class;
    }

    public function incStock($id)
    {
        $res = $this->getModel()::getDb()->find($id);
        if ($res) {
            if ($res['is_limit']) $res->limit_num++;
            if ($res->sales > 1) $res->sales--;
            $res->save();
        }

    }

    public function decStock($id)
    {
        $res = $this->getModel()::getDb()->find($id);
        if (!$res) {
            return false;
        }
        $res->sales++;

        if ($res['is_limit']) {
            if ($res['limit_num'] > 0) {
                $res->limit_num--;
            } else {
                return false;
            }
        }
        $res->save();
        return true;
    }

    public function clear($id,$field)
    {
        $this->getModel()::getDB()->where($field, $id)->delete();
    }


}

