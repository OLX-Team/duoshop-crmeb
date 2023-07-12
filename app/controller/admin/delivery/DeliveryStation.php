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

use app\common\repositories\delivery\DeliveryStationRepository;
use app\common\repositories\system\config\ConfigClassifyRepository;
use app\common\repositories\system\config\ConfigRepository;
use app\common\repositories\system\config\ConfigValueRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use crmeb\basic\BaseController;
use think\App;

class DeliveryStation extends BaseController
{
    protected $repository;

    public function __construct(App $app, DeliveryStationRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function deliveryForm()
    {
        return app('json')->success(formToData($this->repository->deliveryForm()));
    }

    public function saveDeliveryConfig()
    {
        $status = $this->request->param('delivery_status') == 1 ? 1 : 0;
        $type = $this->request->param('delivery_type') == 1 ? 1 : 2;
        if ($type == 1) {
            $data = $this->request->params([
                'delivery_type',
                'dada_app_key',
                'dada_app_sercret',
                'dada_source_id'
            ]);
        } else {
            $data = $this->request->params([
                'delivery_type',
                'uupt_appkey',
                'uupt_app_id',
                'uupt_open_id',
            ]);
        }
        $data['delivery_status'] = $status;
        $cid = app()->make(ConfigClassifyRepository::class)->keyById('delivery_config');
        if (!$cid) return app('json')->fail('保存失败');
        app()->make(ConfigValueRepository::class)->save($cid, $data, 0);
        return app('json')->success('保存成功');
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','station_name','status','mer_id']);
        $data = $this->repository->sysList($where, $page, $limit);
        return app('json')->success($data);
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id, null);
        return app('json')->success($data);
    }

    public function getBalance()
    {
        return app('json')->success($this->repository->getBalance());
    }

    public function getRecharge()
    {
        return app('json')->success($this->repository->getRecharge());
    }

    /**
     * TODO 充值记录
     * @author Qinii
     * @day 2/18/22
     */
    public function payLst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['mer_id','date']);
        $where['type'] = 20;
        $data = app()->make(ServeOrderRepository::class)->getList($where, $page, $limit);
        return app('json')->success($data);
    }

    public function options()
    {
        return app('json')->success($this->repository->getOptions(null));
    }
}
