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


use app\common\repositories\store\order\StoreOrderProfitsharingRepository;
use crmeb\interfaces\ListenerInterface;
use crmeb\jobs\OrderProfitsharingJob;
use crmeb\services\TimerService;
use think\facade\Queue;

class AutoOrderProfitsharingListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(1000 * 60 * 20, function () {
            request()->clearCache();
            $day = (int)systemConfig('sys_refund_timer') ?: 15;
            $time = strtotime('-' . $day . ' day');
            $ids = app()->make(StoreOrderProfitsharingRepository::class)->getAutoProfitsharing(date('Y-m-d H:i:s', $time));
            foreach ($ids as $id) {
                Queue::push(OrderProfitsharingJob::class, $id);
            }
        });
    }
}
