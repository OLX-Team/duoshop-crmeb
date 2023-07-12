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

use app\common\repositories\store\ExcelRepository;
use crmeb\services\TimerService;
use Swoole\Timer;
use think\facade\Log;
use crmeb\interfaces\ListenerInterface;

class ExcelFileDelListen extends TimerService implements ListenerInterface
{
    public function handle($event): void
    {
        $this->tick(1000 * 60 * 60, function () {
            $make = app()->make(ExcelRepository::class);
            $time = date('Y-m-d H:i:s', strtotime("-" . 3 . " day"));
            $data = $make->getDelByTime($time);
            foreach ($data as $id => $path) {
                try {
                    $make->del($id, $path);
                } catch (\Exception $e) {
                    Log::info('自动删除导出文件失败' . var_export($id, true));
                }
            }
        });
    }
}
