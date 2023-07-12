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


namespace crmeb\listens;


use app\common\repositories\store\order\StoreGroupOrderRepository;
use crmeb\interfaces\ListenerInterface;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Log;

class AutoCancelGroupOrderListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(60000, function () {
            $storeGroupOrderRepository = app()->make(StoreGroupOrderRepository::class);
            request()->clearCache();
            $timer = ((int)systemConfig('auto_close_order_timer')) ?: 15;
            $time = date('Y-m-d H:i:s', strtotime("- $timer minutes"));
            $groupOrderIds = $storeGroupOrderRepository->getTimeOutIds($time);
            foreach ($groupOrderIds as $id) {
                try {
                    $storeGroupOrderRepository->cancel($id);
                } catch (\Exception $e) {
                    Log::info('自动关闭订单失败' . var_export($id, 1));
                }
            }
        });
    }
}
