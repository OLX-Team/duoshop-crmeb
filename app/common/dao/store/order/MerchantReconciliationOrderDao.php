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
use app\common\model\store\order\MerchantReconciliationOrder as model;

class MerchantReconciliationOrderDao extends BaseDao
{
   public function getModel(): string
   {
       return model::class;
   }


   public function search($where)
   {
       return ($this->getModel()::getDB())->when(isset($where['reconciliation_id']) && $where['reconciliation_id'] !== '',function ($query)use ($where){
        $query->where('reconciliation_id',$where['reconciliation_id']);
       })->when(isset($where['type']) && $where['type'] !== '',function ($query)use ($where){
           $query->where('type',$where['type']);
       });
   }
}
