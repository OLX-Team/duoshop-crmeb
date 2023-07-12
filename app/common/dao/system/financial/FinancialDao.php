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


namespace app\common\dao\system\financial;


use app\common\dao\BaseDao;
use app\common\model\system\financial\Financial;

class FinancialDao extends BaseDao
{

    protected function getModel(): string
    {
        return Financial::class;
    }

    public function search(array $where)
    {
        $query = Financial::hasWhere('merchant',function($query) use ($where){
            $query->when(isset($where['is_trader']) && $where['is_trader'] !=='',function($query) use($where){
                $query->where('is_trader',$where['is_trader']);
            });
            $query->when(isset($where['type_id']) && $where['type_id'] !=='',function($query) use($where){
                $query->where('type_id',$where['type_id']);
            });
            $query->when(isset($where['category_id']) && $where['category_id'] !=='',function($query) use($where){
                $query->where('category_id',$where['category_id']);
            });
            $query->where('is_del',0);
        });

        $query->when(isset($where['status']) && $where['status'] !=='',function($query) use($where){
                $query->where('Financial.status',$where['status']);
            })
            ->when(isset($where['financial_type']) && $where['financial_type'] !=='',function($query) use($where){
                $query->where('Financial.financial_type',$where['financial_type']);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !=='',function($query) use($where){
                $query->where('Financial.mer_id',$where['mer_id']);
            })
            ->when(isset($where['financial_status']) && $where['financial_status'] !=='',function($query) use($where){
                $query->where('Financial.financial_status',$where['financial_status']);
            })
            ->when(isset($where['keyword']) && $where['keyword'] !=='',function($query) use($where){
                $query->join('SystemAdmin A','Financial.admin_id = A.admin_id')
                    ->field('A.real_name,A.admin_id,A.account')
                    ->whereLike('A.real_name|A.account',"%{$where['keyword']}%");
            })
            ->when(isset($where['keywords_']) && $where['keywords_'] !=='',function($query) use($where){
                $query->join('MerchantAdmin M','Financial.mer_admin_id = M.merchant_admin_id')
                    ->field('M.real_name,M.account,M.merchant_admin_id')
                    ->whereLike('M.real_name|M.account',"%{$where['keywords_']}%");
            })
            ->when(isset($where['financial_id']) && $where['financial_id'] !=='',function($query) use($where){
                $query->where('Financial.financial_id',$where['financial_id']);
            })
            ->when(isset($where['date']) && $where['date'] !=='',function($query) use($where){
                getModelTime($query,$where['date'],'Financial.create_time');
            })
            ->when(isset($where['is_del']) && $where['is_del'] !=='',function($query) use($where){
                $query->where('Financial.is_del',$where['is_del']);
            })
            ->when(isset($where['type']) && $where['type'] !=='',function($query) use($where){
                $query->where('Financial.type',$where['type']);
            });;
        $query->order('Financial.create_time DESC');

        return $query;
    }

}
