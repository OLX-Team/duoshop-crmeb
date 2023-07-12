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

use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\order\StoreOrderReceiptRepository;
use app\common\repositories\user\UserReceiptRepository;
use app\common\repositories\store\order\StoreOrderRepository;

class OrderReceipt extends BaseController
{
    protected $repository;

    public function __construct(App $app, StoreOrderReceiptRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * TODO 列表
     * @return mixed
     * @author Qinii
     * @day 2020-10-17
     */
    public function Lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status', 'date', 'receipt_sn','username','order_type','keyword']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * TODO 平台列表
     * @return mixed
     * @author Qinii
     * @day 2020-10-17
     */
    public function getList()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['status', 'date', 'receipt_sn','username','order_type','keyword','mer_id']);
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }


    public function setRecipt()
    {
        $ids = $this->request->param('ids');
        if(!$ids) return app('json')->fail('请选择需要合并的发票');
        $this->repository->merExists($ids,$this->request->merId());
        return app('json')->success($this->repository->setRecipt($ids,$this->request->merId()));
    }

    /**
     * TODO 开票
     * @return mixed
     * @author Qinii
     * @day 2020-10-17
     */
    public function saveRecipt()
    {
        $data = $this->request->param(['ids','receipt_sn','receipt_price','receipt_no','mer_mark']);
        $this->repository->merExists($data['ids'],$this->request->merId());
        if(!is_numeric($data['receipt_price']) || $data['receipt_price'] < 0)
            return app('json')->fail('发票信息金额格式错误');
        //if(!$data['receipt_no'])return app('json')->fail('请填写发票号');
        $this->repository->save($data);
        return app('json')->success('开票成功');
    }

    /**
     * TODO 备注form
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-10-17
     */
    public function markForm($id)
    {
        return app('json')->success(formToData($this->repository->markForm($id)));
    }

    /**
     * TODO 备注
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-10-17
     */
    public function mark($id)
    {
        if(!$this->repository->getWhereCount(['order_receipt_id' => $id,'mer_id' => $this->request->merId()]))
            return app('json')->fail('数据不存在');
        $data = $this->request->params(['mer_mark']);
        $this->repository->update($id,$data);
        return app('json')->success('备注成功');
    }


    public function detail($id)
    {
        $mer_id = $this->request->merId();
        $where = [$this->repository->getPk() => $id];
        if($mer_id) $where['mer_id'] = $mer_id;
        $data = $this->repository->getSearch($where)->find();
        if(!$data) return app('json')->fail('数据不存在');
        if($data['receipt_info']->receipt_type == 1 ){
            $title = $data['receipt_info']->receipt_title_type == 1 ? '个人电子普通发票' : '企业电子普通发票';
        }else{
            $title = '企业专用纸质发票';
        }
        $data['title'] = $title;
        return app('json')->success($data);
    }

    public function update($id)
    {
        $data = $this->request->params(['receipt_no','mer_mark']);
        if(!empty($data['receipt_no'])) $data['status'] = 1;
        $where = [$this->repository->getPk() => $id,'mer_id' => $this->request->merId()];
        $res = $this->repository->getSearch($where)->find();
        if(!$res) return app('json')->fail('数据不存在');
        $this->repository->updateBySn($res['receipt_sn'],$data);
        return app('json')->success('编辑成功');
    }
}
