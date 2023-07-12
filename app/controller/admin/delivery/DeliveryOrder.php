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


namespace app\controller\admin\delivery;

use app\common\repositories\delivery\DeliveryOrderRepository;
use crmeb\basic\BaseController;
use think\App;

class DeliveryOrder extends BaseController
{
    protected $repository;

    public function __construct(App $app, DeliveryOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','station_name','status','mer_id','date','order_sn','station_type']);
        $data = $this->repository->sysList($where, $page, $limit);
        return app('json')->success($data);
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id, null);
        return app('json')->success($data);
    }

    public function title()
    {
        $data = $this->repository->getTitle();
        return app('json')->success($data);
    }
}
