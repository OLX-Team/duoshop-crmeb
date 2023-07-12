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
use app\common\repositories\store\StoreSeckillActiveRepository;
use app\common\repositories\store\product\ProductAssistRepository;
use app\common\repositories\store\product\ProductGroupRepository;
use app\common\repositories\store\product\ProductPresellRepository;

class SeckillTImeCheckListen extends TimerService implements ListenerInterface
{
    public function handle($event): void
    {
        $this->tick(1000 * 60, function () {
            $make = app()->make(StoreSeckillActiveRepository::class);
            $make_1 = app()->make(ProductAssistRepository::class);
            $make_2 = app()->make(ProductPresellRepository::class);
            $make_3 = app()->make(ProductGroupRepository::class);
            try {
                $make->valActiveStatus();
                $make_1->valActiveStatus();
                $make_2->valActiveStatus();
                $make_3->valActiveStatus();
            } catch (\Exception $e) {
                Log::info('自动检测秒杀结束失败' . date('Y-m-d H:i:s', time()));
            }
        });
    }
}
