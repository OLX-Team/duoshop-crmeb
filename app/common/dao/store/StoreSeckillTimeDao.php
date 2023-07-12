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

use app\common\model\store\StoreSeckillTime;
use app\common\dao\BaseDao;

class StoreSeckillTimeDao extends BaseDao
{

    /**
     * TODO
     * @return string
     * @author Qinii
     * @day 2020-07-30
     */
    protected function getModel(): string
    {
        return StoreSeckillTime::class;
    }

    public function getTime($status)
    {
        foreach (StoreSeckillTime::ISTIME as $k => $item){
            if($status && $k !== 24){
                $time [] = ['value' => $k,  'label' => $item];
            }
            if(!$status && $k !== 0){
                $time [] = ['value' => $k,  'label' => $item];
            }
        }
        return $time;
    }


    public function search(array $where)
    {
        $query = $this->getModel()::getDB()
            ->when(isset($where['status']) && $where['status'] !== '',function($query) use($where){
                $query->where('status',$where['status']);
            })
            ->when(isset($where['title']) && $where['title'] !== '',function($query) use($where){
                $query->where('title','like','%'.$where['title'].'%');
            })
            ->when(isset($where['start_time']) && $where['start_time'] !== '',function($query) use($where){
                $query->whereTime('start_time','<=',intval($where['start_time']));
            })
            ->when(isset($where['end_time']) && $where['end_time'] !== '',function($query) use($where){
                $query->whereTime('end_time','>=',intval($where['end_time']));
            });
        $query->order('start_time ASC');
        return $query;
    }

    /**
     * TODO 开始时间 在别的时间段中
     * @param $time
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function valStartTime($time,$id)
    {
        return $this->getModel()::getDB()
            ->when($id,function ($query)use($id){
                $query->where($this->getPk(),'<>',$id);
            })->where('start_time','<=',$time)->where('end_time','>',$time)->count();
    }

    /**
     * TODO 结束时间在别的时间段中
     * @param $time
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function valEndTime($time,$id)
    {
        return $this->getModel()::getDB()
            ->when($id,function ($query)use($id){
                $query->where($this->getPk(),'<>',$id);
            })->where('start_time','<',$time)->where('end_time','>=',$time)->count();
    }

    /**
     * TODO 时间段包含了别的时间段
     * @param array $data
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-31
     */
    public function valAllTime(array $data,$id)
    {
        return $this->getModel()::getDB()
            ->when($id,function ($query)use($id){
                $query->where($this->getPk(),'<>',$id);
            })->where('start_time','>',$data['start_time'])->where('end_time','<=',$data['end_time'])->count();
    }

}
