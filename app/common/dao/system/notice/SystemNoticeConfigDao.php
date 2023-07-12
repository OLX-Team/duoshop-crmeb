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


namespace app\common\dao\system\notice;


use app\common\dao\BaseDao;
use app\common\model\system\notice\SystemNoticeConfig;

class SystemNoticeConfigDao extends BaseDao
{

    protected function getModel(): string
    {
        return SystemNoticeConfig::class;
    }


    public function getNoticeStatusByKey(string $key, string $field)
    {
        $value = $this->getModel()::getDb()->where('notice_key',$key)->value($field);
        return $value == 1  ? true  : false;
    }

    public function getNoticeStatusByConstKey(string $key)
    {
        $value = $this->getModel()::getDb()->where('const_key',$key)->field('notice_sys,notice_wechat,notice_routine,notice_sms')->find();
        return $value;
    }

    public function search($where)
    {
        $query = $this->getModel()::getDb()
            ->when(isset($where['is_sms']) && $where['is_sms'] != '', function($query){
                $query->whereIn('notice_sms',[0,1]);
            })
            ->when(isset($where['is_routine']) && $where['is_routine'] != '', function($query){
                $query->whereIn('notice_routine',[0,1]);
            })
            ->when(isset($where['is_wechat']) && $where['is_wechat'] != '', function($query){
                $query->whereIn('notice_wechat',[0,1]);
            })
        ;
        return $query;
    }

    public function getSubscribe()
    {
        $arr = [];
        $res = $this->search([])->where(['notice_routine' => 1])->with(['routineTemplate'])->select()->toArray();
        foreach ($res as $re) {
            $arr[$re['const_key']] = $re['routineTemplate']['tempid'] ?? '';
        }
        return $arr;
    }
}
