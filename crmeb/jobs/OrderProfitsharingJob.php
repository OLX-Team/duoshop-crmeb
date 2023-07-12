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


namespace crmeb\jobs;


use app\common\repositories\store\order\StoreOrderProfitsharingRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class OrderProfitsharingJob implements JobInterface
{

    public function fire($job, $id)
    {
        $make = app()->make(StoreOrderProfitsharingRepository::class);
        $profitsharing = $make->get((int)$id);
        if (!$profitsharing || $profitsharing->status != 0) {
            $job->delete();
            return;
        }
        try {
            $make->profitsharing($profitsharing);
        } catch (\Exception $e) {
            Log::info('自动分账失败:' . $e->getMessage());
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
