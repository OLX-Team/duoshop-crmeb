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

use app\common\repositories\system\serve\ServeOrderRepository;
use crmeb\services\DeliverySevices;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\delivery\DeliveryStationRepository;
use app\validate\merchant\DeliveryStationValidate;
use think\exception\ValidateException;

class DeliveryStation extends BaseController
{
    protected $repository;

    public function __construct(App $app, DeliveryStationRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','station_name','status','type','date']);
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->merList($where, $page, $limit);
        return app('json')->success($data);
    }

    public function getTypeList()
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','station_name']);
        $where['mer_id'] = $this->request->merId();
        $where['type'] = systemConfig('delivery_type');
        $where['status'] = 1;
        $data = $this->repository->merList($where, $page, $limit);
        return app('json')->success($data);
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id,$this->request->merId());
        return app('json')->success($data);
    }

    public function create()
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        $data = $this->checkParams();
        $data['mer_id'] = $this->request->merId();
        $this->repository->save($data);
        return app('json')->success('添加成功');
    }

    public function update($id)
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        $data = $this->checkParams();
        $this->repository->edit($id, $this->request->merId(), $data);
        return app('json')->success('编辑成功');
    }

    public function delete($id)
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        $this->repository->destory($id, $this->request->merId());
        return app('json')->success('删除成功');
    }

    public function switchWithStatus($id)
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        $status = $this->request->param('status') == 1 ? 1 : 0;
        $this->repository->update($id,['status' => $status]);
        return app('json')->success('修改成功');
    }

    public function markForm($id)
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        return app('json')->success(formToData($this->repository->markForm($id, $this->request->merId())));
    }

    public function mark($id)
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        $data = $this->request->params(['mark']);
        $this->repository->update($id, $data);
        return app('json')->success('备注成功');
    }

    public function checkParams()
    {
        $data = $this->request->params([
            'station_name',
            'business',
            'station_address',
            'lng',
            'lat',
            'contact_name',
            'phone',
            'username',
            'password',
            ['status',1],
            'city_name',
        ]);
        $make = app()->make(DeliveryStationValidate::class);
        $data['type'] = systemConfig('delivery_type');
        if ($data['type'] == DeliverySevices::DELIVERY_TYPE_DADA) {
            $make->scene('dada')->check($data);
        } else {
            $make->check($data);
            [$data['lng'],$data['lat']] = gcj02ToBd09($data['lng'],$data['lat']);
        }
        return $data;
    }

    public function getBusiness()
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        $data = $this->repository->getBusiness();
        return app('json')->success($data);
    }

    public function options()
    {
        $where = [
            'status' => 1,
            'mer_id' => $this->request->merId(),
            'type' => systemConfig('delivery_type'),
        ];
        return app('json')->success($this->repository->getOptions($where));
    }

    public function select()
    {
        $where = [
            'mer_id' => $this->request->merId(),
        ];
        return app('json')->success($this->repository->getOptions($where));
    }

    public function getCityLst()
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        return app('json')->success($this->repository->getCityLst());
    }

    /**
     * TODO 充值记录
     * @author Qinii
     * @day 2/18/22
     */
    public function payLst()
    {

        [$page, $limit] = $this->getPage();
        $where = [
            'type' => 20,
            'mer_id' => $this->request->merId(),
            'date' => $this->request->param('date'),
        ];
        $data = app()->make(ServeOrderRepository::class)->getList($where, $page, $limit);
        $data['delivery_balance'] = $this->request->merchant()->delivery_balance;
        return app('json')->success($data);
    }

    public function getQrcode()
    {
        if (systemConfig('delivery_status') != 1) throw new ValidateException('未开启同城配送');
        $data['pay_type'] = $this->request->param('pay_type',1);
        $data['price']  = $this->request->param('price',10);
        if (!is_numeric($data['price']) || $data['price'] <= 0 )
            return app('json')->fail('支付金额不正确');
        $res = app()->make(ServeOrderRepository::class)->QrCode($this->request->merId(), 'delivery', $data);
        $res['delivery_balance'] = $this->request->merchant()->delivery_balance;
        return app('json')->success($res);
    }
}
