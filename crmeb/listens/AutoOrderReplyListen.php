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


use app\common\repositories\store\order\StoreOrderRepository;
use crmeb\interfaces\ListenerInterface;
use crmeb\jobs\OrderReplyJob;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Queue;

class AutoOrderReplyListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(1000 * 60 * 60, function () {
            request()->clearCache();
            if (systemConfig('open_auto_reply') === '0') {
                return;
            }
            $storeOrderRepository = app()->make(StoreOrderRepository::class);
            $time = date('Y-m-d H:i:s', strtotime('- 7 day'));
            $ids = $storeOrderRepository->getFinishTimeoutIds($time);
            foreach ($ids as $id) {
                Queue::push(OrderReplyJob::class, $id);
            }
        });
    }
}
