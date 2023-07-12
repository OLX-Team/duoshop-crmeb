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
use think\exception\HttpResponseException;
use think\facade\Route;
use think\Response;

class InstallMiddleware extends BaseMiddleware
{

    public function before(Request $request)
    {
        if(!file_exists(__DIR__.'/../../../install/install.lock')){
            throw new HttpResponseException( Response::create('/install.html', 'redirect')->code(302));
        }
    }

    public function after(Response $response)
    {
    }
}
