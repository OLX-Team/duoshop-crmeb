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

use app\Request;
use crmeb\interfaces\MiddlewareInterface;
use crmeb\services\LockService;
use think\Response;

class RequestLockMiddleware implements MiddlewareInterface
{
    final public function handle(Request $request, \Closure $next, ...$args): Response
    {
        $params = $request->route();
        if (!count($params) || in_array(strtolower($request->method()), ['get', 'options']) || $request->rule()->getOption('_lock', true) === false) {
            return $next($request);
        }
        ksort($params);
        $key = 're:' . $request->rule()->getName() . ':' . implode('-', $params);
        return app()->make(LockService::class)->exec($key, function () use ($next, $request) {
            return $next($request);
        }, 8);
    }
}
