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

namespace app\controller\merchant\store\delivery;

use app\common\repositories\delivery\DeliveryOrderRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use think\App;
use crmeb\basic\BaseController;
use think\exception\ValidateException;

class DeliveryOrder extends BaseController
{
    protected $repository;

    public function __construct(App $app, DeliveryOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','station_id','status','date','order_sn','station_type']);
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->merList($where, $page, $limit);
        return app('json')->success($data);
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id,$this->request->merId());
        return app('json')->success($data);
    }

    public function cancelForm($id)
    {
        return app('json')->success(formToData($this->repository->cancelForm($id)));
    }

    public function cancel($id)
    {
        $reason = $this->request->params(['reason','cancel_reason']);
        if (empty($reason['reason']))
            return app('json')->fail('取消理由不能为空');
        $this->repository->cancel($id, $this->request->merId(), $reason);
        return app('json')->success('取消成功');
    }

    public function delete($id)
    {
        $this->repository->destory($id, $this->request->merId());
        return app('json')->success('删除成功');
    }

    public function switchWithStatus($id)
    {
        $status = $this->request->param('status') == 1 ? 1 : 0;
        $this->repository->update($id,['status' => $status]);
        return app('json')->success('修改成功');
    }


}
