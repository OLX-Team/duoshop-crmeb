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


namespace app\controller\admin\order;


use app\common\repositories\store\ExcelRepository;
use app\common\repositories\store\order\StoreOrderProfitsharingRepository;
use crmeb\basic\BaseController;
use crmeb\services\ExcelService;
use think\App;

class OrderProfitsharing extends BaseController
{
    protected $repository;

    public function __construct(App $app, StoreOrderProfitsharingRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getList()
    {
        $where = $this->request->params(['type', 'status', 'mer_id', 'keyword', 'profit_date', 'date']);
        $merId = $this->request->merId();
        if ($merId) {
            $where['mer_id'] = $merId;
        }
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit, (bool)$merId));
    }

    public function again($id)
    {
        if (!$model = $this->repository->get((int)$id)) {
            return app('json')->fail('分账单不存在');
        }
        if ($model->status != -2) {
            return app('json')->fail('分账单状态操作,不能分账');
        }
        if ($this->repository->profitsharing($model)) {
            return app('json')->success('分账成功');
        }
        return app('json')->fail('分账失败');
    }

    public function export()
    {
        $where = $this->request->params(['type', 'status', 'mer_id', 'keyword', 'profit_date', 'date']);
        $merId = $this->request->merId();
        if ($merId) {
            $where['mer_id'] = $merId;
        }
        [$page, $limit] = $this->getPage();
        $data = app()->make(ExcelService::class)->profitsharing($where,$page,$limit);
        return app('json')->success($data);
    }
}
