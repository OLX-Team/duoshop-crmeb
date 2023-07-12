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
namespace app\controller\api\server;

use app\common\repositories\store\order\StoreRefundOrderRepository;
use crmeb\basic\BaseController;
use think\App;

class StoreRefundOrder extends BaseController
{
    protected $merId;
    protected $repository;
    protected $service_id;

    public function __construct(App $app, StoreRefundOrderRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->merId = $this->request->route('merId');
        $this->service_id = $this->request->serviceInfo()->service_id;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['order_type','delivery_id']);
        $where['mer_id'] = $this->merId;
        return app('json')->success($this->repository->getListByService($where,$page,$limit));
    }

    public function detail($id)
    {
        $data = $this->repository->getWhere([$this->repository->getPk() => $id,'mer_id' => $this->merId],'*',['order','refundProduct.product','user']);
        return app('json')->success($data);
    }

    public function getRefundPrice($id)
    {
        return app('json')->success($this->repository->serverRefundDetail($id,$this->merId));
    }
    public function express($id)
    {
        $data['refund'] = $this->repository->getWhere(['refund_order_id' => $id,'mer_id'=> $this->merId,'status' =>2],'*', ['refundProduct.product']);
        if(!$data['refund'])
            return app('json')->fail('订单信息或状态错误');

        $data['express'] = $this->repository->express($id);
        return app('json')->success($data);
    }

    public function switchStatus($id)
    {
        if(!$this->repository->getStatusExists($this->merId,$id))
            return app('json')->fail('信息或状态错误');
        $status = ($this->request->param('status') == 1) ? 1 : -1;
        event('refund.status',compact('id','status'));
        if($status == 1){
            $data = $this->request->params(['mer_delivery_user','mer_delivery_address','phone']);
            if ($data['phone'] && isPhone($data['phone']))
                return app('json')->fail('请输入正确的手机号');
            $data['status'] = $status;
            $this->repository->agree($id,$data,$this->service_id);
        }else{
            $fail_message = $this->request->param('fail_message','');
            if($status == -1 && empty($fail_message))
                return app('json')->fail('未通过必须填写');
            $data['status'] = $status;
            $data['fail_message'] = $fail_message;
            $this->repository->refuse($id,$data, $this->service_id);
        }
        return app('json')->success('审核成功');
    }

    public function refundPrice($id)
    {
        if(!$this->repository->getRefundPriceExists($this->merId,$id))
            return app('json')->fail('信息或状态错误');
        $this->repository->adminRefund($id,$this->service_id);
        return app('json')->success('退款成功');
    }

    public function mark($id){
        if(!$this->repository->getExistsById($this->merId,$id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id,['mer_mark' => $this->request->param('mer_mark','')]);

        return app('json')->success('备注成功');
    }

}
