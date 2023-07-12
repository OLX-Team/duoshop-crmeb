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
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Log;

class AuthCancelPresellOrderListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(1000 * 60 * 1.5, function () {
            $presellOrderRepository = app()->make(PresellOrderRepository::class);
            $ids = $presellOrderRepository->getTimeOutIds(date('Y-m-d H:i:s'));
            foreach ($ids as $id) {
                try {
                    $presellOrderRepository->cancel($id);
                } catch (\Exception $e) {
                    Log::info('自动关闭尾款订单失败' . var_export($id, 1));
                }
            }
        });
    }
}
