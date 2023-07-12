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


namespace crmeb\services;


use Swoole\Timer;
use think\facade\Log;

class TimerService
{
    public function tick($limit, $fn)
    {
        Timer::tick($limit, function () use ($fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                Log::error('定时器报错[' . class_basename($this) . ']: ' . $e->getMessage());
            }
        });
    }
}
