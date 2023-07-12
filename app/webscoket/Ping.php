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


namespace app\webscoket;


use think\facade\Cache;

/**
 * Class Ping
 * @package app\webscoket
 * @author xaboy
 * @day 2020-04-29
 */
class Ping
{
    /**
     * @var \Redis
     */
    protected $redis;


    const CACHE_PINK_KEY = 'ws.p.';


    const CACHE_SET_KEY = 'ws.s';


    /**
     * Ping constructor.
     */
    public function __construct()
    {
        $this->redis = Cache::store('redis')->handler();
        $this->destroy();
    }

    /**
     * @param $id
     * @param $time
     * @param int $timeout
     * @author xaboy
     * @day 2020-04-29
     */
    public function createPing($id, $time, $timeout = 0)
    {
        $this->updateTime($id, $time, $timeout);
        $this->redis->sAdd(self::CACHE_SET_KEY, $id);
    }

    /**
     * @param $id
     * @param $time
     * @param int $timeout
     * @author xaboy
     * @day 2020-04-29
     */
    public function updateTime($id, $time, $timeout = 0)
    {
        $this->redis->set(self::CACHE_PINK_KEY . $id, $time, $timeout);
    }

    /**
     * @param $id
     * @author xaboy
     * @day 2020-05-06
     */
    public function removePing($id)
    {
        $this->redis->del(self::CACHE_PINK_KEY . $id);
        $this->redis->del(self::CACHE_SET_KEY, $id);
    }

    /**
     * @param $id
     * @return bool|string
     * @author xaboy
     * @day 2020-04-29
     */
    public function getLastTime($id)
    {
        return $this->redis->get(self::CACHE_PINK_KEY . $id);
    }

    /**
     * @author xaboy
     * @day 2020-04-29
     */
    public function destroy()
    {
        $members = $this->redis->sMembers(self::CACHE_SET_KEY) ?: [];
        foreach ($members as $k => $member) {
            $members[$k] = self::CACHE_PINK_KEY . $member;
        }
        if (count($members))
            $this->redis->del(self::CACHE_SET_KEY, ...$members);
    }
}
