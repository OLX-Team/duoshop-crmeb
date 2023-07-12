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


namespace app\controller\api\store\order;


use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use crmeb\basic\BaseController;
use think\App;
use think\exception\HttpResponseException;

class StoreOrderVerify extends BaseController
{
    protected $user;

    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function detail($merId, $id, StoreOrderRepository $repository)
    {
        $order = $repository->codeByDetail($id);
        if (!$order) return app('json')->fail('订单不存在');
        if ($order->mer_id != $merId)
            return app('json')->fail('没有权限查询该订单');
        return app('json')->success($order);
    }

    public function verify($merId, $id, StoreOrderRepository $repository)
    {
        $data = $this->request->params(['data','verify_code']);
        $repository->verifyOrder($id, $merId, $data,$this->request->serviceInfo()->service_id);
        return app('json')->success('订单核销成功');
    }
}
