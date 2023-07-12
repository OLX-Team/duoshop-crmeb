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


namespace app\controller\api\user;


use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\controller\merchant\Common;
use crmeb\basic\BaseController;
use think\App;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Db;
use think\response\Json;

class Admin extends BaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function orderStatistics($merId, StoreOrderRepository $repository)
    {
        $order = $repository->OrderTitleNumber($merId, null);
        $common = app()->make(Common::class);
        $data = [];
        $data['today'] = $common->mainGroup('today', $merId);
        $data['yesterday'] = $common->mainGroup('yesterday', $merId);
        $data['month'] = $common->mainGroup('month', $merId);
        return app('json')->success(compact('order', 'data'));
    }

    public function orderDetail($merId, StoreOrderRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        list($start, $stop) = $this->request->params([
            ['start', strtotime(date('Y-m'))],
            ['stop', time()],
        ], true);
        if ($start == $stop) return app('json')->fail('参数有误');
        if ($start > $stop) {
            $middle = $stop;
            $stop = $start;
            $start = $middle;
        }
        $where = $this->request->has('start') ? ['dateRange' => compact('start', 'stop')] : [];
        $list = $repository->orderGroupNumPage($where, $page, $limit, $merId);
        return app('json')->success($list);
    }

    public function orderList($merId, StoreOrderRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status']);
        $where['mer_id'] = $merId;
        $where['is_del'] = 0;
        return app('json')->success($repository->merchantGetList($where, $page, $limit));
    }

    public function order($merId, $id, StoreOrderRepository $repository)
    {
        $detail = $repository->getDetail($id);
        if (!$detail)
            return app('json')->fail('订单不存在');
        if ($detail['mer_id'] != $merId)
            return app('json')->fail('没有权限');
        return app('json')->success($detail->toArray());
    }


    protected function checkOrderAuth($merId, $id)
    {
        if (!app()->make(StoreOrderRepository::class)->existsWhere(['mer_id' => $merId, 'order_id' => $id]))
            throw new ValidateException('没有权限');
    }

    public function mark($merId, $id, StoreOrderRepository $repository)
    {
        $this->checkOrderAuth($merId, $id);
        $data = $this->request->params(['remark']);
        $repository->update($id, $data);
        return app('json')->success('备注成功');
    }

    public function price($merId, $id, StoreOrderRepository $repository)
    {
        $this->checkOrderAuth($merId, $id);

        $data = $this->request->params(['total_price', 'pay_postage']);

        if ($data['total_price'] < 0 || $data['pay_postage'] < 0)
            return app('json')->fail('金额不可未负数');
        if (!$repository->merStatusExists((int)$id, $merId))
            return app('json')->fail('订单信息或状态错误');
        $repository->eidt($id, $data, $this->request->serviceInfo()->service_id);
        return app('json')->success('修改成功');
    }

    public function delivery($merId, $id, StoreOrderRepository $repository)
    {
        $this->checkOrderAuth($merId, $id);
        if (!$repository->merDeliveryExists((int)$id, $merId,1))
            return app('json')->fail('订单信息或状态错误');
        $type = $this->request->param('delivery_type');
        if($type == 4){
            if(!systemConfig('crmeb_serve_dump')) return app('json')->fail('电子面单功能未开启');
            $params = $this->request->params([
                'delivery_name',
                'from_name',
                'from_tel',
                'from_addr',
                'temp_id',
            ]);
            $repository->dump($id,$merId,$params);
        } else {
            $data  = $this->request->params([
                'delivery_type',
                'delivery_name',
                'delivery_id',
            ]);
            if(preg_match('/([\x81-\xfe][\x40-\xfe])/',$data['delivery_id']))
                return app('json')->fail('请输入正确的单号/电话');
            $repository->delivery($id, $merId, $data);
        }
        return app('json')->success('发货成功');
    }

    public function payPrice($merId, StoreOrderRepository $repository)
    {
        list($start, $stop, $month) = $this->request->params([
            ['start', strtotime(date('Y-m'))],
            ['stop', time()],
            'month'
        ], true);

        if ($month) {
            $start = date('Y/m/d', strtotime(getStartModelTime('month')));
            $stop = date('Y/m/d H:i:s', strtotime('+ 1day'));
            $front = date('Y/m/d', strtotime('first Day of this month', strtotime('-1 day', strtotime('first Day of this month'))));
            $end = date('Y/m/d H:i:s', strtotime($start . ' -1 second'));
        } else {
            if ($start == $stop) return app('json')->fail('参数有误');
            if ($start > $stop) {
                $middle = $stop;
                $stop = $start;
                $start = $middle;
            }
            $space = bcsub($stop, $start, 0);//间隔时间段
            $front = bcsub($start, $space, 0);//第一个时间段

            $front = date('Y/m/d H:i:s', $front);
            $start = date('Y/m/d H:i:s', $start);
            $stop = date('Y/m/d H:i:s', $stop);
            $end = date('Y/m/d H:i:s', strtotime($start . ' -1 second'));
        }
        $frontPrice = $repository->dateOrderPrice($front . '-' . $end, $merId);
        $afterPrice = $repository->dateOrderPrice($start . '-' . date('Y/m/d H:i:s', strtotime($stop . '-1 second')), $merId);
        $chartInfo = $repository->chartTimePrice($start, date('Y/m/d H:i:s', strtotime($stop . '-1 second')), $merId);
        $data['chart'] = $chartInfo;//营业额图表数据
        $data['time'] = $afterPrice;//时间区间营业额
        $increase = (float)bcsub((string)$afterPrice, (string)$frontPrice, 2); //同比上个时间区间增长营业额
        $growthRate = abs($increase);
        if ($growthRate == 0) $data['growth_rate'] = 0;
        else if ($frontPrice == 0) $data['growth_rate'] = bcmul($growthRate, 100, 0);
        else $data['growth_rate'] = (int)bcmul((string)bcdiv((string)$growthRate, (string)$frontPrice, 2), '100', 0);//时间区间增长率
        $data['increase_time'] = abs($increase); //同比上个时间区间增长营业额
        $data['increase_time_status'] = $increase >= 0 ? 1 : 2; //同比上个时间区间增长营业额增长 1 减少 2

        return app('json')->success($data);
    }

    /**
     * @param StoreOrderRepository $repository
     * @return Json
     * @author xaboy
     * @day 2020/8/27
     */
    public function payNumber($merId, StoreOrderRepository $repository)
    {
        list($start, $stop, $month) = $this->request->params([
            ['start', strtotime(date('Y-m'))],
            ['stop', time()],
            'month'
        ], true);

        if ($month) {
            $start = date('Y/m/d', strtotime(getStartModelTime('month')));
            $stop = date('Y/m/d H:i:s', strtotime('+ 1day'));
            $front = date('Y/m/d', strtotime('first Day of this month', strtotime('-1 day', strtotime('first Day of this month'))));
            $end = date('Y/m/d H:i:s', strtotime($start . ' -1 second'));
        } else {
            if ($start == $stop) return app('json')->fail('参数有误');
            if ($start > $stop) {
                $middle = $stop;
                $stop = $start;
                $start = $middle;
            }
            $space = bcsub($stop, $start, 0);//间隔时间段
            $front = bcsub($start, $space, 0);//第一个时间段

            $front = date('Y/m/d H:i:s', $front);
            $start = date('Y/m/d H:i:s', $start);
            $stop = date('Y/m/d H:i:s', $stop);
            $end = date('Y/m/d H:i:s', strtotime($start . ' -1 second'));
        }
        $frontNumber = $repository->dateOrderNum($front . '-' . $end, $merId);
        $afterNumber = $repository->dateOrderNum($start . '-' . date('Y/m/d H:i:s', strtotime($stop . '-1 second')), $merId);
        $chartInfo = $repository->chartTimeNum($start . '-' . date('Y/m/d H:i:s', strtotime($stop . '-1 second')), $merId);
        $data['chart'] = $chartInfo;//订单数图表数据
        $data['time'] = $afterNumber;//时间区间订单数
        $increase = $afterNumber - $frontNumber; //同比上个时间区间增长订单数
        $growthRate = abs($increase);
        if ($growthRate == 0) $data['growth_rate'] = 0;
        else if ($frontNumber == 0) $data['growth_rate'] = bcmul($growthRate, 100, 0);
        else $data['growth_rate'] = (int)bcmul((string)bcdiv((string)$growthRate, (string)$frontNumber, 2), '100', 0);//时间区间增长率
        $data['increase_time'] = abs($increase); //同比上个时间区间增长营业额
        $data['increase_time_status'] = $increase >= 0 ? 1 : 2; //同比上个时间区间增长营业额增长 1 减少 2

        return app('json')->success($data);
    }

    public function getFormData($merId)
    {
        $config = [
            'mer_from_com',
            'mer_from_name',
            'mer_from_tel',
            'mer_from_addr',
            'mer_config_siid',
            'mer_config_temp_id'
        ];
        $data = merchantConfig($merId,$config);
        return app('json')->success($data);
    }
}
