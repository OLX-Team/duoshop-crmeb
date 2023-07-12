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

namespace app\controller\admin\points;


use crmeb\jobs\BatchDeliveryJob;
use crmeb\services\ExcelService;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\order\StoreOrderRepository;
use think\facade\Queue;

class Order extends BaseController
{


    /**
     * @var StoreOrderRepository
     */
    protected $repository;


    /**
     * StoreProduct constructor.
     * @param App $app
     * @param StoreOrderRepository $repository
     */
    public function __construct(App $app, StoreOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'date','status','order_sn','product_id','nickname','phone','uid','store_name']);
        $where['activity_type'] = 20;
        if ($where['status'] == -10) {
            unset($where['status']);
            $where['is_del'] = 1;
        }
        return app('json')->success($this->repository->pointsOrderAdminList($where, $page, $limit));
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function detail($id)
    {
        $data = $this->repository->getOne($id, 0);
        if (!$data) return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }

    public function getStatus($id)
    {
        [$page, $limit] = $this->getPage();
        $where['user_type'] = $this->request->param('user_type');
        $where['date'] = $this->request->param('date');
        $where['id'] = $id;
        $data = $this->repository->getOrderStatus($where, $page, $limit);
        if (!$data) return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }

    /**
     * TODO 发货
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function delivery($id)
    {
        $split = $this->request->params(['is_split',['split',[]]]);
        if (!$this->repository->merDeliveryExists($id, $this->request->merId()))
            return app('json')->fail('订单信息或状态错误');
        $data  = $this->request->params([
            'delivery_type',
            'delivery_name',
            'delivery_id',
            'remark',
        ]);
        if (!$data['delivery_type'] || $data['delivery_type'] != 3  && (!$data['delivery_name'] || !$data['delivery_id']))
            return app('json')->fail('填写配送信息');

        $this->repository->runDelivery($id,$this->request->merId(), $data, $split, 'delivery');
        return app('json')->success('发货成功');
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 7/26/21
     */
    public function batchDelivery()
    {
        $params = $this->request->params([
            'order_id',
            'delivery_id',
            'delivery_type',
            'delivery_name',
            'remark',
        ]);
        if (!in_array($params['delivery_type'], [1,2,3]))
            return app('json')->fail('发货类型错误');
        if (!$params['order_id'])
            return app('json')->fail('需要订单ID');
        $data = ['mer_id' => $this->request->merId(), 'data' => $params];
        Queue::push(BatchDeliveryJob::class, $data);
        return app('json')->success('开始批量发货');
    }

    /**
     * TODO 导出文件
     * @author Qinii
     * @day 2020-07-30
     */
    public function excel()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','date','status','order_sn','product_id']);
        $data = app()->make(ExcelService::class)->pointsOrder($where,$page,$limit);
        return app('json')->success($data);
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$this->repository->userDelExists($id, $this->request->merId()))
            return app('json')->fail('订单信息或状态错误');
        $this->repository->update($id,['is_system_del' => 1]);
        return app('json')->success('删除成功');
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function remarkForm($id)
    {
        return app('json')->success(formToData($this->repository->pointsMarkForm($id)));
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function remark($id)
    {
        if (!$this->repository->getOne($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $data = $this->request->params(['remark']);
        $this->repository->update($id, $data);

        return app('json')->success('备注成功');
    }

    public function express($id)
    {
        if (!$this->repository->getWhereCount(['order_id' => $id]))
            return app('json')->fail('订单信息或状态错误');
        return app('json')->success($this->repository->express($id,null));
    }

}
