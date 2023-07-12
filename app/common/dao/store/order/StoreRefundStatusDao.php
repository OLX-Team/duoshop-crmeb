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


namespace app\common\dao\store\order;


use app\common\dao\BaseDao;
use app\common\model\store\order\StoreRefundStatus;

class StoreRefundStatusDao extends BaseDao
{

    protected function getModel(): string
    {
        return StoreRefundStatus::class;
    }

    public function search($id)
    {
        return $query = StoreRefundStatus::getDB()->where('refund_order_id', $id);
    }
}
