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

use app\common\repositories\store\service\StoreServiceRepository;
use think\exception\HttpResponseException;
use app\Request;
use think\Response;
use Throwable;

class MerchantServerMiddleware extends BaseMiddleware
{

    public function before(Request $request)
    {
        $this->merId = $this->request->route('merId');

        $type = $this->getArg(0);
        $field = 'customer';
        switch ($type) {
            case 0:
                $field = 'customer';
                break;
            case 1:
                $field = 'is_goods';
                break;
        }
        $userInfo = $this->request->userInfo();
        $service = app()->make(StoreServiceRepository::class)->getService($userInfo->uid, $this->merId);
        if (!$service && $userInfo->main_uid) {
            $service = app()->make(StoreServiceRepository::class)->getService($userInfo->main_uid, $this->merId);
        }

        if (!$service || !$service->$field) {
            throw new HttpResponseException(app('json')->fail('您没有权限操作'));
        }
        $request->macro('serviceInfo', function () use (&$service) {
            return $service;
        });
    }

    public function after(Response $response)
    {
        // TODO: Implement after() method.
    }
}
