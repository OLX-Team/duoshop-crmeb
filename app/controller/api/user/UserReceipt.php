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

use app\common\repositories\store\order\StoreOrderReceiptRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\user\UserReceiptRepository;
use app\validate\api\UserReceiptValidate;

class UserReceipt extends BaseController
{
    /**
     * @var UserReceiptRepository
     */
    protected $repository;

    /**
     * UserReceipt constructor.
     * @param App $app
     * @param UserReceiptRepository $repository
     */
    public function __construct(App $app, UserReceiptRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function create(UserReceiptValidate $validate)
    {
        $data = $this->checkParams($validate);
        $data['uid'] = $this->request->uid();
        if($data['is_default'] == 1) $this->repository->clearDefault($this->request->uid());
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function lst()
    {
        $where = $this->request->params(['receipt_title_type','receipt_type','is_default']);
        $where['uid'] = $this->request->uid();
        return app('json')->success($this->repository->getList($where));
    }

    public function order(StoreOrderReceiptRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where['status'] = $this->request->param('status');
        $where['uid'] = $this->request->uid();
        $where['order_type'] = 8;

        $data = $repository->getList($where, $page, $limit);
        $data['list']->append(['storeOrder.orderProduct']);
        return app('json')->success($data);
    }

    public function orderDetail($id, StoreOrderReceiptRepository $repository)
    {
        $receipt = $repository->getWhere(['order_receipt_id' => $id, 'uid' => $this->request->uid()],'*',[
            'storeOrder.orderProduct',
            'merchant' => function($query) {
                $query->field('mer_id,service_phone')->append(['services_type']);
            }
        ]);
        if (!$receipt) return app('json')->fail('发票信息不存在');
        return app('json')->success($receipt);
    }

    public function isDefault($id)
    {
        $res = $this->repository->uidExists($id,$this->request->uid());
        if(!$res) return app('json')->fail('信息丢失');
        $this->repository->isDefault($id,$this->request->uid());
        return app('json')->success('修改成功');
    }

    public function update($id,UserReceiptValidate $validate)
    {
        $data = $this->checkParams($validate);
        if(!$this->repository->uidExists($id,$this->request->uid())) return app('json')->fail('信息丢失');
        if($data['is_default'] == 1) $this->repository->clearDefault($this->request->uid());
        $this->repository->update($id,$data);
        return app('json')->success('编辑成功');
    }

    public function detail($id)
    {
        $where = [
            'uid' => $this->request->uid(),
            'user_receipt_id' => $id
        ];
        return app('json')->success($this->repository->detail($where));

    }

    public function delete($id)
    {
        if(!$this->repository->uidExists($id,$this->request->uid()))
            return app('json')->fail('信息丢失');
        $res = $this->repository->getIsDefault($this->request->uid());
        if($res && $res['user_receipt_id'] == $id)
            return app('json')->fail('默认项不可删除');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    public function checkParams(UserReceiptValidate $validate)
    {
        $data = $this->request->params(['receipt_type','receipt_title','receipt_title_type','duty_paragraph','email','bank_name','bank_code','address','tel','is_default']);
        $validate->check($data);
        return $data;
    }
}
