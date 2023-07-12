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

namespace app\common\dao\system\serve;

use app\common\dao\BaseDao;
use app\common\model\system\serve\ServeOrder;

class ServeOrderDao extends BaseDao
{

    protected function getModel(): string
    {
        return ServeOrder::class;
    }

    public function search($where)
    {
        $query = ServeOrder::hasWhere('merchant',function($query) use($where) {

            $query->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use($where){
                $query->whereLike('mer_keyword|real_name|mer_name',"%{$where['keyword']}%");
            });
            $query->when(isset($where['is_trader']) && $where['is_trader'] !== '', function ($query) use($where){
                $query->where('is_trader',$where['is_trader']);
            });
            $query->when(isset($where['category_id']) && $where['category_id'] !== '', function ($query) use($where){
                $query->where('category_id',$where['category_id']);
            });
            $query->when(isset($where['type_id']) && $where['type_id'] !== '', function ($query) use($where){
                $query->where('type_id',$where['type_id']);
            });
            $query->where('is_del',0);
        });

        $query->when(isset($where['type']) && $where['type'] !== '', function ($query) use($where){
            $query->where('ServeOrder.type',$where['type']);
        });

        $query->when(isset($where['date']) && $where['date'] !== '', function ($query) use($where){
           getModelTime($query,$where['date'],'ServeOrder.create_time');
        });

        $query->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use($where){
            $query->where('ServeOrder.mer_id',$where['mer_id']);
        });

        $query->when(isset($where['status']) && $where['status'] !== '', function ($query) use($where){
            $query->where('ServeOrder.status',$where['status']);
        });

        $query->when(isset($where['is_del']) && $where['is_del'] !== '', function ($query) use($where){
            $query->where('ServeOrder.is_del',$where['is_del']);
        });


        return $query;
    }

}
