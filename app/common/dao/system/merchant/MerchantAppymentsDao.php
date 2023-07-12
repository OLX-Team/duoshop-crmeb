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
use app\common\model\system\merchant\MerchantApplyments;

class MerchantAppymentsDao extends BaseDao
{
    protected function getModel(): string
    {
        return MerchantApplyments::class;
    }

    public function search(array $where)
    {
        $query = $this->getModel()::getDB()
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('mer_id', $where['mer_id']);
            })
            ->when(isset($where['uid']) && $where['uid'] !== '', function ($query) use ($where) {
                $query->where('uid', $where['uid']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', (int)$where['status']);
            })
            ->when(isset($where['mer_applyments_id']) && $where['mer_applyments_id'] !== '', function ($query) use ($where) {
                $query->where('mer_applyments_id', $where['mer_applyments_id']);
            })
            ->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date']);
            })
            ->where('is_del', 0);

        return $query;
    }



}
