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
use crmeb\jobs\SendSmsJob;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Queue;

class AutoSendPayOrderSmsListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(1000 * 60 * 5, function () {
            $storeGroupOrderRepository = app()->make(StoreGroupOrderRepository::class);
            $time = date('Y-m-d H:i:s', strtotime("- 10 minutes"));
            $groupOrderIds = $storeGroupOrderRepository->getTimeOutIds($time, true);
            foreach ($groupOrderIds as $id) {
                Queue::push(SendSmsJob::class, [
                    'tempId' => 'ORDER_PAY_FALSE',
                    'id' => $id
                ]);
                $storeGroupOrderRepository->isRemind($id);
            }
        });
    }
}
