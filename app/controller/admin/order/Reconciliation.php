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

use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\store\order\MerchantReconciliationRepository as repository;

class Reconciliation extends BaseController
{
    protected $repository;

    public function __construct(App $app,repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    public function lst()
    {
        [$page,$limit] = $this->getPage();
        $where = $this->request->params(['date','status','keyword','reconciliation_id']);
        return app('json')->success($this->repository->getList($where,$page,$limit));
    }


    public function create($id)
    {
        if(!app()->make(MerchantRepository::class)->merExists($id))
            return app('json')->fail('商户不存在');
        $data = $this->request->params([
            'date',                     //时间
            'order_type',               //订单 全选1
            'refund_type',              //退款 全选1
            ['order_ids',[]],           //订单
            ['order_out_ids',[]],       //排除,不参与对账的订单ID
            ['refund_out_ids',[]],      //排除,不参与对账的退款订单ID
            ['refund_order_ids',[]]     //退款段id
        ]);
        $data['adminId'] = $this->request->adminId();
        $this->repository->create($id,$data);
        return app('json')->success('对账单生成成功');
    }

    /**
     * TODO 确认打款
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-06-15
     */
    public function switchStatus($id)
    {
        if(!$this->repository->getWhereCountById($id))
            return app('json')->fail('数据不存在或状态错误');
        $status = $this->request->param('status') == 1 ? 1 : 0;
        $data['is_accounts'] = $status;
        if($status == 1) $data['accounts_time'] = date('Y-m-d H:i:s',time());
        $this->repository->switchStatus($id,$data);
        return app('json')->success('修改成功');
    }



    public function markForm($id)
    {
        if(!$this->repository->getWhereCount([$this->repository->getPk() => $id]))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->adminMarkForm($id)));
    }

    public function mark($id)
    {
        if(!$this->repository->getWhereCount([$this->repository->getPk() => $id]))
            return app('json')->fail('数据不存在');
        $data = $this->request->params(['admin_mark']);
        $this->repository->update($id,$data);
        return app('json')->success('备注成功');
    }
}
