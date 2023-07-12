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


use app\common\repositories\store\order\PresellOrderRepository;
use crmeb\interfaces\ListenerInterface;
use crmeb\jobs\AutoChangeStatusActivityJob;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Log;
use think\facade\Queue;

class AuthCancelActivityListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(1000 * 60 * 60 * 2, function () {
            try {
                Queue::push(AutoChangeStatusActivityJob::class,[]);
            } catch (\Exception $e) {

            }
        });
    }
}
