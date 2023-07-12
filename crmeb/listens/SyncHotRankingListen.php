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
use crmeb\jobs\SyncProductTopJob;
use crmeb\services\TimerService;
use think\facade\Log;
use think\facade\Queue;

class SyncHotRankingListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $hot = systemConfig('hot_ranking_switch');
        if (!$hot)  return ;
        $time = systemConfig('hot_ranking_time');
        $time = ($time && $time > 1) ?: 1 ;
        $this->tick(1000 * 60 * 60 * $time, function () {
            request()->clearCache();
            try{
                Queue::push(SyncProductTopJob::class, []);
            }catch (\Exception $e) {
                Log::info('热卖排行错误：'.var_export([$e->getMessage()],1));
            }
        });
    }
}
