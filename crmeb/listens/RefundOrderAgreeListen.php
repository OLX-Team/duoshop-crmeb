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

use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Log;
use crmeb\interfaces\ListenerInterface;
use app\common\repositories\store\order\StoreRefundOrderRepository;

class RefundOrderAgreeListen extends TimerService implements ListenerInterface
{
    public function handle($event): void
    {
        $this->tick(1000 * 60 * 5, function () {
            $make = app()->make(StoreRefundOrderRepository::class);
            request()->clearCache();
            $merAgree = systemConfig('mer_refund_order_agree') ?? 7;
            $time = date('Y-m-d H:i:s', strtotime('-' . $merAgree . ' day'));
            $data = $make->getTimeOutIds($time);
            foreach ($data as $id) {
                try {
                    $make->adminRefund($id);
                } catch (\Exception $e) {
                    Log::info('自动退款失败' . var_export($id, true));
                }
            }
        });
    }
}
