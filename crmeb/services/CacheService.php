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

use think\cache\driver\Redis;
use think\facade\Cache;
use think\facade\Config;

/**
 * crmeb 缓存类
 * Class CacheService
 * @package crmeb\services
 * @mixin \Redis
 */
class CacheService
{
    const TAG_TOPIC = 'topic';
    const TAG_CONFIG = 'config';
    const TAG_COMMUNITY = 'community';
    const TAG_BRAND = 'brand';
    const TAG_CATEGORY = 'category';
    const TAG_GROUP_DATA = 'group_data';
    const TAG_MERCHANT = 'merchant';

    protected $handler;
    protected $tag;
    protected $type;

    /**
     * @param int $admin
     * @param string $tag
     */
    public function __construct($type, $tag)
    {
        $key = config('app.app_key');
        $tagLst = ['__cache_' . $key];

        if ($type) {
            $tagLst[] = '__cache_mer_' . $key . '_' . $type;
            $tagLst[] = '__cache_mer_' . $key;
        } else {
            $tagLst[] = '__cache_sys_' . $key;
        }

        if ($tag) {
            $tagLst[] = '__cache_tag_' . $key . '_' . $type . '_' . $tag;
        }
        $this->tag = $tag;
        $this->type = $type;
        $this->handler = Cache::store('file')->tag($tagLst);
    }

    public static function create($admin, $tag)
    {
        return new static($admin, $tag);
    }

    /**
     * 清除所以缓存
     */
    public static function clearAll()
    {
        Cache::store('file')->tag('__cache_' . config('app.app_key'))->clear();
    }

    /**
     * 清除商户缓存
     */
    public static function clearMerchantAll()
    {
        Cache::store('file')->tag('__cache_mer_' . config('app.app_key'))->clear();
    }

    /**
     * 清除平台缓存
     */
    public static function clearSystem()
    {
        Cache::store('file')->tag('__cache_sys_' . config('app.app_key'))->clear();
    }

    /**
     * @param int $merId
     * 清除指定商户缓存
     */
    public static function clearMerchant($merId)
    {
        Cache::store('file')->tag('__cache_mer_' . config('app.app_key') . '_' . $merId)->clear();
    }

    /**
     * 根据tag清除缓存
     * @param $merId
     * @param $tag
     */
    public static function clearByTag($merId, $tag)
    {
        Cache::store('file')->tag('__cache_tag_' . config('app.app_key') . '_' . $merId . '_' . $tag)->clear();
    }

    public static function delete($key)
    {
        Cache::store('file')->delete($key);
    }

    /**
     * @param $key
     * @return string
     * 生成 key
     */
    public function cacheKey($key)
    {
        if (is_array($key)) {
            $key = json_encode($key, JSON_UNESCAPED_UNICODE);
        }
        return '__sys_cache_' . config('app.app_key') . $this->type . $this->tag . $key;
    }

    /**
     * @param string|array $key
     * @param $cache
     * @param int $expire
     */
    public function set($key, $cache, $expire = 3600)
    {
        $this->handler->set($this->cacheKey($key), $cache, $expire);
    }

    /**
     * @param string|array $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->handler->get($this->cacheKey($key), $default);
    }

    /**
     * @param string|array $key
     * @return mixed
     */
    public function has($key)
    {
        return $this->handler->has($this->cacheKey($key));
    }

    /**
     * @param string|array $key
     * @param $value
     * @param int $expire
     * @return mixed
     */
    public function remember($key, $value, $expire = 3600)
    {
        return $this->handler->remember($this->cacheKey($key), $value, $expire);
    }

}
