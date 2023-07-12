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


use app\common\repositories\system\admin\AdminLogRepository;
use app\Request;
use crmeb\services\SwooleTaskService;
use think\Response;

class LogMiddleware extends BaseMiddleware
{

    public function before(Request $request)
    {
        // TODO: Implement before() method.
    }


    public function after(Response $response)
    {
        if ($this->request->method() == 'GET') return;
        SwooleTaskService::log($this->request->merId(), AdminLogRepository::parse($this->request));
    }
}
