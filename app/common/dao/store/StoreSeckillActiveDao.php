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

namespace app\common\dao\store;

use app\common\model\store\StoreSeckillActive;
use app\common\dao\BaseDao;
use app\common\repositories\store\product\SpuRepository;

class StoreSeckillActiveDao extends BaseDao
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-07-30
     */
    protected function getModel(): string
    {
        return StoreSeckillActive::class;
    }


    /**
     * TODO 搜索
     * @param array $where
     * @return mixed
     * @author Qinii
     * @day 2020-08-05
     */
    public function search(array $where)
    {
        $query = $this->getModel()::getDB()
            ->when(isset($where['status']) && $where['status'] !== '',function($query) use($where){
                $query->where('status',$where['status']);
            })
            ->when(isset($where['start_day']) && $where['start_day'] !== '',function($query) use($where){
                $query->whereTime('start_day','<=',$where['start_day']);
            })
            ->when(isset($where['end_day']) && $where['end_day'] !== '',function($query) use($where){
                $query->whereTime('end_day','>',$where['end_day']);
            })
            ->when(isset($where['start_time']) && $where['start_time'] !== '',function($query) use($where){
                $query->whereTime('start_time','<=',$where['start_time']);
            })
            ->when(isset($where['end_time']) && $where['end_time'] !== '',function($query) use($where){
                $query ->whereTime('end_time','>',$where['end_time']);
            })
            ->when(isset($where['product_id']) && $where['product_id'] !== '',function($query) use($where){
                $query->where('product_id',$where['product_id']);
            })
            ->when(isset($where['day']) && $where['day'] !== '',function($query) use($where){
                $query->whereTime('start_day','<=',$where['day'])->whereTime('end_day','>',$where['day']);
            })
            ->when(isset($where['time']) && $where['time'] !== '',function($query) use($where){
                $query->whereTime('start_time','<=',$where['time'])->whereTime('end_time','>',$where['time']);
            });

        $query->order('start_time DESC');
        return $query;
    }


    /**
     * TODO
     * @param int $productId
     * @param array $data
     * @return mixed
     * @author Qinii
     * @day 2020-08-11
     */
    public function updateByProduct(int $productId,array $data)
    {
        return $this->getModel()::getDB()->where('product_id',$productId)->update($data);
    }

    /**
     * TODO
     * @author Qinii
     * @day 2020-08-11
     */
    public function valActiveStatus()
    {
        $day = date('Y-m-d',time());
        $_h = date('H',time());
        $id = $this->getModel()::getDB()->where('status',1)
            ->whereTime('end_day','<=',$day)
            ->whereTime('end_time','<',$_h)
            ->column('seckill_active_id');
        if($id) {
            $this->getModel()::getDB()->where('seckill_active_id', 'in', $id)->update(['status' => -1]);
            $where = [
                'product_type' => 1,
                'activity_ids' => $id
            ];
            app()->make(SpuRepository::class)->getSearch($where)->update(['status' => 0]);
        }
    }

    /**
     * TODO 不同状态商品
     * @param $status
     * @return mixed
     * @author Qinii
     * @day 2020-08-19
     */
    public function getStatus($status)
    {
        $day = date('Y-m-d',time());
        $_h = date('H',time());
        $query = $this->getModel()::getDB();
        if($status == 1) //未开始
            $query->where('status','<>',-1)->where(function($query)use ($day,$_h){
                $query->whereTime('start_day','>',$day)->whereOr(function($query)use($day,$_h){
                    $query->whereTime('start_day','<=',$day)->where('start_time','>',$_h);
                });
            });

        if($status == 2)//进行中
            $query->where('status',1)
                ->whereTime('start_day','<=',$day)->whereTime('end_day','>',$day)
                ->where('start_time','<=',$_h)->where('end_time','>',$_h);

        if($status == 3) //结束
            $query->where('status',-1)->whereOr(function($query)use($day,$_h){
                $query->whereTime('end_day','<',$day)
                    ->whereOr(function($query)use($day,$_h){
                        $query->whereTime('start_day','<=',$day)->whereTime('end_day','>=',$day)->where('end_time','<=',$_h);
                });
            });
        return $query;
    }
}
