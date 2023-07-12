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


namespace app\common\dao\system\merchant;


use app\common\dao\BaseDao;
use app\common\model\system\merchant\FinancialRecord;

class FinancialRecordDao extends BaseDao
{

    protected function getModel(): string
    {
        return FinancialRecord::class;
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/6/9
     */
    public function getSn()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $orderId = 'jy' . $msectime . mt_rand(10000, max(intval($msec * 10000) + 10000, 98369));
        return $orderId;
    }

    public function inc(array $data, $merId)
    {
        $data['mer_id'] = $merId;
        $data['financial_pm'] = 1;
        $data['financial_record_sn'] = $this->getSn();
        return $this->create($data);
    }

    public function dec(array $data, $merId)
    {
        $data['mer_id'] = $merId;
        $data['financial_pm'] = 0;
        $data['financial_record_sn'] = $this->getSn();
        return $this->create($data);
    }

    public function search(array $where)
    {
        $query = $this->getModel()::getDB()
            ->when(isset($where['financial_type']) && $where['financial_type'] !== '', function ($query) use ($where) {
                $query->whereIn('financial_type', $where['financial_type']);
            })
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('mer_id', $where['mer_id']);
            })
            ->when(isset($where['user_info']) && $where['user_info'] !== '', function ($query) use ($where) {
                $query->where('user_info', $where['user_info']);
            })
            ->when(isset($where['user_id']) && $where['user_id'] !== '', function ($query) use ($where) {
                $query->where('user_id', $where['user_id']);
            })
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->whereLike('order_sn|user_info|financial_record_sn', "%{$where['keyword']}%");
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'create_time');
            })
            ->when(isset($where['is_mer']) && $where['is_mer'] !== '', function ($query) use ($where) {
                if($where['is_mer']){
                    $query->where('mer_id',$where['is_mer'])->where('type','in',[0,1]);
                }else{
                    $query->where('type','in',[1,2]);
                }
            });
        return $query;
    }

    /**
     * TODO 根据条件和时间查询出相对类型的数量个金额
     * @param int $type
     * @param array $where
     * @param string $date
     * @param array $financialType
     * @return array
     * @author Qinii
     * @day 4/14/22
     */
    public function getDataByType(int $type, array $where, string  $date, array $financialType)
    {
        if (empty($financialType)) return [0,0];
        $query = $this->search($where)->where('financial_type','in',$financialType);

        if($type == 1) {
            $query->whereDay('create_time',$date);
        } else {
            $query->whereMonth('create_time',$date);
        }

        $count  = $query->group('order_id')->count();
        $number = $query->sum('number');

        return [$count,$number];
    }
}
