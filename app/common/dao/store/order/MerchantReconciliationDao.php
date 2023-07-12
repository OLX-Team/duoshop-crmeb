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
use app\common\model\store\order\MerchantReconciliation as model;
use app\common\repositories\system\admin\AdminRepository;
use app\common\repositories\system\merchant\MerchantRepository;

class MerchantReconciliationDao extends BaseDao
{
   public function getModel(): string
   {
       return model::class;
   }

   public function search(array $where)
   {
       $query = ($this->getModel()::getDB())
           ->when(isset($where['mer_id']) && $where['mer_id'] != '' ,function($query)use($where){
               $query->where('mer_id',$where['mer_id']);
           })->when(isset($where['status']) && $where['status'] != '' ,function($query)use($where){
               $query->where('status',$where['status']);
           })->when(isset($where['is_accounts']) && $where['is_accounts'] != '' ,function($query)use($where){
               $query->where('is_accounts',$where['is_accounts']);
           })->when(isset($where['date']) && $where['date'] != '' ,function($query)use($where){
               getModelTime($query,$where['date']);
           })->when(isset($where['reconciliation_id']) && $where['reconciliation_id'] != '' ,function($query)use($where){
               $query->where('reconciliation_id',$where['reconciliation_id']);
           })
           ->when(isset($where['keyword']) && $where['keyword'] !== '',function($query)use($where){
               $make = app()->make(AdminRepository::class);
               $admin_id = $make->getSearch(['real_name' => $where['keyword']],null,false)->column('admin_id');
               $query->where(function($query) use($admin_id,$where){
                   if(isset($where['mer_id'])){
                        $query->where('admin_id','in',$admin_id);
                   }else {
                       $mer_make = app()->make(MerchantRepository::class);
                       $mer_id = $mer_make->getSearch(['keyword' => $where['keyword']])->column('mer_id');
                       $query->where('admin_id','in',$admin_id)->whereOr('mer_id','in',$mer_id);
                   }
               });
           });
       return $query->order('create_time DESC,status DESC');
   }

}
