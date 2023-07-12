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
use think\Response;

class CheckSiteOpenMiddleware implements MiddlewareInterface
{

    public function handle(Request $request, \Closure $next): Response
    {
        if (systemConfig('site_open') === '0') {
            return app('json')->make(501, '站点已关闭');
        }
        return $next($request);
    }
}
