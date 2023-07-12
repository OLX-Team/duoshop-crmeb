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


namespace app\common\dao\system\sms;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\system\sms\SmsRecord;
use think\db\BaseQuery;
use think\db\exception\DbException;

/**
 * Class SmsRecordDao
 * @package app\common\dao\system\sms
 * @author xaboy
 * @day 2020-05-18
 */
class SmsRecordDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return SmsRecord::class;
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-05-18
     */
    public function search(array $where)
    {
        return SmsRecord::getDB()->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
            $query->where('resultcode', $where['type']);
        })->order('create_time DESC');
    }

    /**
     * @return int
     * @author xaboy
     * @day 2020-05-18
     */
    public function count()
    {
        return SmsRecord::count($this->getPk());
    }

    /**
     * @param $record_id
     * @param $resultcode
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-05-18
     */
    public function updateRecordStatus($record_id, $resultcode)
    {
        return SmsRecord::getDB()->where('record_id', $record_id)->update(['resultcode' => $resultcode]);
    }


    /**
     * @param $time
     * @return array
     * @author xaboy
     * @day 2020/6/9
     */
    public function getTimeOutIds($time)
    {
        return SmsRecord::getDB()->where('resultcode', null)->where('create_time', '<=', $time)->column('record_id');
    }
}
