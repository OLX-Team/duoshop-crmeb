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

use crmeb\interfaces\ListenerInterface;
use crmeb\jobs\GauaranteeCountJob;
use crmeb\services\TimerService;
use think\facade\Log;

class GuaranteeCountListen extends TimerService implements ListenerInterface
{
    public function handle($event): void
    {
        $this->tick(1000 * 60 * 15, function () {
            try {
                queue(GauaranteeCountJob::class,[]);
            } catch (\Exception $e) {
                Log::info('自动更新保障服务数量失败' . var_export($e, true));
            }
        });
    }
}
