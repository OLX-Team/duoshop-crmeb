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


namespace app\common\repositories\store;


use app\common\repositories\BaseRepository;
use think\facade\Cache;

class IntegralRepository extends BaseRepository
{
    const CACHE_KEY = 'sys_int_next_day';

    public function getNextDay()
    {
        return strtotime(date('Y-m-d', strtotime('first day of +1 month 00:00:00')));
    }

    public function getInvalidDay()
    {
        $month = systemConfig('integral_clear_time');
        if ($month <= 0) return 0;
        return strtotime('last day of -' . $month . ' month 23:59:59', $this->getTimeoutDay() - 1);
    }

    public function getTimeoutDay($clear = false)
    {
        $driver = Cache::store('file');
        if (!$driver->has(self::CACHE_KEY) || $clear) {
            $driver->set(self::CACHE_KEY, $this->getNextDay(), (20 * 24 * 3600) + 3600 * 12);
        }
        return $driver->get(self::CACHE_KEY);
    }

    public function clearTimeoutDay()
    {
        Cache::store('file')->delete(self::CACHE_KEY);
    }
}
