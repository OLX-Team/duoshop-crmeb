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


use app\common\repositories\store\broadcast\BroadcastGoodsRepository;
use app\common\repositories\store\broadcast\BroadcastRoomRepository;
use crmeb\interfaces\ListenerInterface;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Cache;
use think\facade\Log;

class SyncBroadcastStatusListen extends TimerService implements ListenerInterface
{

    public function handle($event): void
    {
        $this->tick(1000 * 60 * 5, function () {
            $broadcastGoodsRepository = app()->make(BroadcastGoodsRepository::class);
            try {
                $broadcastGoodsRepository->syncGoodStatus();
            } catch (\Exception $e) {
                Log::error('同步直播商品:' . $e->getMessage());
            }
        });

        $this->tick(1000 * 60 * 5, function () {
            if (Cache::has('_sys_break_b_room')) {
                return;
            }
            $broadcastRoomRepository = app()->make(BroadcastRoomRepository::class);
            try {
                $broadcastRoomRepository->syncRoomStatus();
            } catch (\Exception $e) {
                if ($e instanceof \EasyWeChat\Core\Exceptions\HttpException && $e->getCode() == '48001') {
                    Cache::set('_sys_break_b_room', 1, 3600);
                }
                Log::error('同步直播间:' . $e->getMessage());
            }
        });
    }
}
