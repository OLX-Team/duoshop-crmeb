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

use app\common\repositories\store\order\StoreRefundOrderRepository;
use crmeb\interfaces\JobInterface;
use think\facade\Log;

class CancelGroupBuyingJob implements JobInterface
{

    public function fire($job, $data)
    {
        try{
        //TODO 关闭子团,自动退款,关闭订单
            $make = app()->make(StoreRefundOrderRepository::class);
            $make->autoRefundOrder($data['order_id'], 1, $data['message']);
        }catch (\Exception $exception){
            Log::info(var_export($exception, 1));
        }
        $job->delete();
    }

    public function failed($data)
    {
        // TODO: Implement failed() method.
    }
}
