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

namespace app\controller\merchant\store\order;

use app\common\repositories\store\ExcelRepository;
use app\common\repositories\store\order\MerchantReconciliationRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use crmeb\exceptions\UploadException;
use crmeb\jobs\BatchDeliveryJob;
use crmeb\services\ExcelService;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\order\StoreOrderRepository as repository;
use think\facade\Queue;

class Order extends BaseController
{
    protected $repository;

    /**
     * Product constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function title()
    {
        $where = $this->request->params(['status', 'date', 'order_sn', 'username', 'order_type', 'keywords', 'order_id', 'activity_type','filter_delivery','filter_product']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getStat($where, $where['status']));
    }
    /**
     * 订单列表
     * @return mixed
     * @author Qinii
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status', 'date', 'order_sn', 'username', 'order_type', 'keywords', 'order_id', 'activity_type', 'group_order_sn', 'store_name','filter_delivery','filter_product']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->merchantGetList($where, $page, $limit));
    }

    public function takeTitle()
    {
        $where = $this->request->params(['date', 'order_sn', 'username', 'keywords']);
        $where['take_order'] = 1;
        $where['status'] = -1;
        $where['verify_date'] = $where['date'];
        unset($where['date']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getStat($where, ''));
    }

    /**
     * TODO 自提订单列表
     * @return mixed
     * @author Qinii
     * @day 2020-08-17
     */
    public function takeLst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date', 'order_sn', 'username', 'keywords']);
        $where['take_order'] = 1;
        $where['status'] = -1;
        $where['verify_date'] = $where['date'];
        unset($where['date']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->merchantGetList($where, $page, $limit));
    }

    /**
     *  订单头部统计
     * @return mixed
     * @author Qinii
     */
    public function chart()
    {
        return app('json')->success($this->repository->OrderTitleNumber($this->request->merId(), null));
    }

    /**
     * TODO 自提订单头部统计
     * @return mixed
     * @author Qinii
     * @day 2020-08-17
     */
    public function takeChart()
    {
        return app('json')->success($this->repository->OrderTitleNumber($this->request->merId(), 1));
    }


    /**
     * TODO 订单类型
     * @return mixed
     * @author Qinii
     * @day 2020-08-15
     */
    public function orderType()
    {
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->orderType($where));
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function deliveryForm($id)
    {
        $data = $this->repository->getWhere(['order_id' => $id, 'mer_id' => $this->request->merId(), 'is_del' => 0]);
        if (!$data) return app('json')->fail('数据不存在');
        if (!$data['paid']) return app('json')->fail('订单未支付');
        if (!in_array($data['status'], [0, 1])) return app('json')->fail('订单状态错误');
        return app('json')->success(formToData($this->repository->sendProductForm($id, $data)));
    }

    /**
     * TODO 发货
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function delivery($id)
    {
        $type = $this->request->param('delivery_type');
        $split = $this->request->params(['is_split',['split',[]]]);
        if (!$this->repository->merDeliveryExists($id, $this->request->merId()))
            return app('json')->fail('订单信息或状态错误');
        switch ($type)
        {
            case 3: //虚拟发货
                $data  = $this->request->params([
                    'delivery_type',
                    'remark',
                ]);
                $data['delivery_name'] = '';
                $data['delivery_id'] = '';
                $method = 'delivery';
                break;
            case 4: //电子面单
                if (!systemConfig('crmeb_serve_dump'))
                    return app('json')->fail('电子面单功能未开启');
                $data = $this->request->params([
                    'delivery_type',
                    'delivery_name',
                    'from_name',
                    'from_tel',
                    'from_addr',
                    'temp_id',
                    'remark',
                ]);
                if (!$data['from_name'] ||
                    !$data['delivery_name'] ||
                    !$data['from_tel'] ||
                    !$data['from_addr'] ||
                    !$data['temp_id']
                )
                    return app('json')->fail('填写配送信息');
                $method = 'dump';
                break;
            case 5: //同城配送
                if (systemConfig('delivery_status') != 1)
                    return app('json')->fail('未开启同城配送');
                $data = $this->request->params(['delivery_type', 'station_id', 'mark', ['cargo_weight',0], 'remark',]);
                if ($data['cargo_weight'] < 0) return app('json')->fail('包裹重量能为负数');
                if (!$data['station_id']) return app('json')->fail('请选择门店');
                $method = 'cityDelivery';
                break;
            default: //快递
                $data  = $this->request->params(['delivery_type', 'delivery_name', 'delivery_id', 'remark',]);
                if (!$data['delivery_type'] || !$data['delivery_name'] || !$data['delivery_id'])
                    return app('json')->fail('填写配送信息');
                $method = 'delivery';
                break;
        }
        $res = $this->repository->runDelivery($id,$this->request->merId(), $data, $split, $method);
        return app('json')->success('发货成功',$res);
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
        if (!in_array($params['delivery_type'], [2, 3]))  return app('json')->fail('发货类型错误');
        if (!$params['order_id'])  return app('json')->fail('需要订单ID');
//        $this->repository->batchDelivery($this->request->merId(),$params);
        Queue::push(BatchDeliveryJob::class,  [
            'mer_id' => $this->request->merId(),
            'data' => $params
        ]);
        return app('json')->success('开始批量发货');
    }

    /**
     * TODO 改价form
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function updateForm($id)
    {
        if (!$this->repository->merStatusExists($id, $this->request->merId()))
            return app('json')->fail('订单信息或状态错误');
        return app('json')->success(formToData($this->repository->form($id)));
    }

    /**
     * TODO 改价
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function update($id)
    {
        $data = $this->request->params(['total_price', 'pay_postage']);
        if ($data['total_price'] < 0 || $data['pay_postage'] < 0)
            return app('json')->fail('金额不可未负数');
        if (!$this->repository->merStatusExists($id, $this->request->merId()))
            return app('json')->fail('订单信息或状态错误');
        $this->repository->eidt($id, $data);
        return app('json')->success('修改成功');
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function detail($id)
    {
        $data = $this->repository->getOne($id, $this->request->merId());
        if (!$data) return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function status($id)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date','user_type']);
        $where['id'] = $id;
        if (!$this->repository->getOne($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->getOrderStatus($where, $page, $limit));
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function remarkForm($id)
    {
        return app('json')->success(formToData($this->repository->remarkForm($id)));
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

    /**
     * 核销
     * @param $code
     * @author xaboy
     * @day 2020/8/15
     */
    public function verify($id)
    {
        $data = $this->request->params(['data','verify_code']);
        $this->repository->verifyOrder($id, $this->request->merId(), $data);
        return app('json')->success('订单核销成功');
    }

    public function verifyDetail($code)
    {
        $order = $this->repository->codeByDetail($code);
        if (!$order) return app('json')->fail('订单不存在');
        return app('json')->success($order);
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-11
     */
    public function delete($id)
    {
        if (!$this->repository->userDelExists($id, $this->request->merId()))
            return app('json')->fail('订单信息或状态错误');
        $this->repository->merDelete($id);
        return app('json')->success('删除成功');
    }


    /**
     * TODO 快递查询
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-25
     */
    public function express($id)
    {
        return app('json')->success($this->repository->express($id, $this->request->merId()));
    }

    /**
     * TODO
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-30
     */
    public function reList($id)
    {
        [$page, $limit] = $this->getPage();
        $make = app()->make(MerchantReconciliationRepository::class);
        if (!$make->getWhereCount(['mer_id' => $this->request->merId(), 'reconciliation_id' => $id]))
            return app('json')->fail('数据不存在');
        $where = ['reconciliation_id' => $id, 'type' => 0];
        return app('json')->success($this->repository->reconList($where, $page, $limit));
    }

    /**
     * TODO 导出文件
     * @author Qinii
     * @day 2020-07-30
     */
    public function excel()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status', 'date', 'order_sn', 'order_type', 'username', 'keywords', 'take_order']);
        if ($where['take_order']) {
            $where['status'] = -1;
            $where['verify_date'] = $where['date'];
            unset($where['date']);
            unset($where['order_type']);
        }
        $where['mer_id'] = $this->request->merId();
        $data = app()->make(ExcelService::class)->order($where,$page,$limit);
        return app('json')->success($data);
    }

    /**
     * TODO 打印小票
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-07-30
     */
    public function printer($id)
    {
        $merId = $this->request->merId();
        if (!$this->repository->getWhere(['order_id' => $id, 'mer_id' => $merId]))
            return app('json')->fail('数据不存在');
        $this->repository->batchPrinter($id, $merId);
        return app('json')->success('打印成功');
    }

    /**
     * TODO 导出发货单
     * @return \think\response\Json
     * @author Qinii
     * @day 3/13/21
     */
    public function deliveryExport()
    {
        $where = $this->request->params(['username', 'date', 'activity_type', 'order_type', 'username', 'keywords', 'id']);
        $where['order_ids'] = $this->request->param('ids');
        $where['mer_id'] = $this->request->merId();
        $where['status'] = 0;
        $where['paid'] = 1;
        $make = app()->make(StoreOrderRepository::class);
        if (is_array($where['id'])) $where['order_ids'] = $where['id'];
        $count = $make->search($where)->count();
        if (!$count) return app('json')->fail('没有可导出数据');

        [$page, $limit] = $this->getPage();
        $data = app()->make(ExcelService::class)->delivery($where,$page,$limit);
        return app('json')->success($data);
    }

    public function childrenList($id)
    {
        $data = $this->repository->childrenList($id, $this->request->merId());
        return app('json')->success($data);
    }
}
