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


namespace app\common\middleware;

use app\common\repositories\system\auth\MenuRepository;
use app\common\repositories\system\auth\RoleRepository;
use app\Request;
use Doctrine\Common\Cache\RedisCache;
use http\Exception\InvalidArgumentException;
use think\facade\Cache;
use think\Response;

class BlockerMiddleware extends BaseMiddleware
{
    protected  $key;

    public function before(Request $request)
    {
        $uid = request()->uid();
        $this->key = md5(request()->rule()->getRule() . $uid);
        if (!$this->setMutex($this->key)) {
            throw new InvalidArgumentException('请求太过频繁，请稍后再试');
        }
    }

    public function setMutex(string $key, int $timeout = 10)
    {
        $curTime = time();
        $readMutexKey = "redis:mutex:{$key}";
        $mutexRes = Cache::store('redis')->handler()->setnx($readMutexKey, $curTime + $timeout);
        if ($mutexRes) {
            return true;
        }
        //就算意外退出，下次进来也会检查key，防止死锁
        $time = Cache::store('redis')->handler()->get($readMutexKey);
        if ($curTime > $time) {
            Cache::store('redis')->handler()->del($readMutexKey);
            return Cache::store('redis')->handler()->setnx($readMutexKey, $curTime + $timeout);
        }
        return false;
    }

    public function after(Response $response)
    {
        Cache::store('redis')->handler()->del("redis:mutex:{$this->key}");
        // TODO: Implement after() method.
    }
}
