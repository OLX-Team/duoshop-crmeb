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
use app\common\model\user\UserHistory;

class UserHistoryDao extends BaseDao
{
    protected function getModel(): string
    {
        return UserHistory::class;
    }


    public function createOrUpdate(array $data)
    {
        $ret = $this->getModel()::getDB()->where($data)->find();
        if($ret){
            $ret->update_time = time();
            $ret->save();
        }else{
            $data['update_time'] = time();
            $this->create($data);
        }
    }

    public function search(?int $uid, int $type)
    {
        $query = ($this->getModel()::getDB())->when($uid, function ($query) use ($uid) {
            $query->where('uid', $uid);
        })->when($type, function ($query) use ($type) {
            $query->where('res_type', $type);
        });

        return $query->order('update_time DESC');
    }


    public function deleteBatch($uid,$data)
    {
        if(is_array($data)){
            $this->getModel()::getDB()->where($this->getPk(),'in',$data)->delete();
        }else if($data == 1){
            $this->getModel()::getDB()->where('uid',$uid)->delete();
        }
    }

    public function userTotalHistory($uid)
    {
        return UserHistory::alias('H')->join('store_spu S','S.spu_id = H.res_id')->where('uid',$uid)->where('res_type', 1)->count();
    }

    public function joinSpu($where)
    {
        $query = UserHistory::hasWhere('spu',function($query) use($where){
            $query->when(isset($where['keyword']) && $where['keyword'] !== '', function($query) use ($where){
                $query->whereLike('store_name',"%{$where['keyword']}%");
            });
            $query->where(true);
        });
        $query->where('uid', $where['uid']);
        $query->where('res_type', $where['type']);
        return $query;
    }

}
