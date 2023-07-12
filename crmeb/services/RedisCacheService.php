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

use think\facade\Cache;

/**
 * crmeb 缓存类
 * Class CacheService
 * @package crmeb\services
 * @mixin \Redis
 * @mixin \think\cache\driver\Redis
 */
class RedisCacheService
{

    /**
     * @var \Redis
     */
    protected $handler;

    /**
     * @var \think\cache\driver\Redis
     */
    protected $driver;

    /**
     * @param int $admin
     * @param string $tag
     */
    public function __construct()
    {
        $this->driver = Cache::store('redis');
        $this->handler = $this->driver->handler();
    }

    public function handler()
    {
        return $this->handler;
    }

    public function driver()
    {
        return $this->driver;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->driver, $name)) {
            return call_user_func_array([$this->driver, $name], $arguments);
        }
        return call_user_func_array([$this->handler, $name], $arguments);
    }

}
