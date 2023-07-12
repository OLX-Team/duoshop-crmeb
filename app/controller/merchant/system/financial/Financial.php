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
namespace app\controller\merchant\system\financial;

use app\common\repositories\store\ExcelRepository;
use app\common\repositories\system\financial\FinancialRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\validate\merchant\MerchantFinancialAccountValidate;
use crmeb\basic\BaseController;
use crmeb\services\ExcelService;
use think\App;

class Financial extends BaseController
{
    /**
     * @var FinancialRepository
     */
    protected $repository;

    /**
     * Merchant constructor.
     * @param App $app
     * @param FinancialRepository $repository
     */
    public function __construct(App $app, FinancialRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    /**
     * TODO 转账信息Form
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 3/18/21
     */
    public function accountForm()
    {
        return app('json')->success(formToData($this->repository->financialAccountForm($this->request->merId())));
    }

    /**
     * TODO 转账信息保存
     * @param MerchantFinancialAccountValidate $accountValidate
     * @return \think\response\Json
     * @author Qinii
     * @day 3/18/21
     */
    public function accountSave(MerchantFinancialAccountValidate $accountValidate)
    {
        $data = $this->request->params(['account','financial_type','name','bank','bank_code','wechat','wechat_code','alipay','alipay_code']); //idcard
        $accountValidate->check($data);

        $this->repository->saveAccount($this->request->merId(),$data);
        return app('json')->success('保存成功');
    }

    /**
     * TODO 申请转账form
     * @return \think\response\Json
     * @author Qinii
     * @day 3/19/21
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->applyForm($this->request->merId())));
    }

    /**
     * TODO 申请转账保存
     * @return \think\response\Json
     * @author Qinii
     * @day 3/19/21
     */
    public function createSave()
    {
        $data = $this->request->param(['extract_money','financial_type','mark']);
        $data['mer_admin_id'] = $this->request->adminId();
        $this->repository->saveApply($this->request->merId(),$data);
        return app('json')->success('保存成功');
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 2023/6/10
     */
    public function refundMargin()
    {
        return app('json')->success($this->repository->checkRefundMargin($this->request->merId(), $this->request->adminId()));
    }

    public function refundMarginApply()
    {
        $data = $this->request->params(['type','name','code','pic']);
        $this->repository->refundMargin($this->request->merId(), $this->request->adminId(),$data);
        return app('json')->success('提交成功');
    }

    /**
     * TODO 列表
     * @author Qinii
     * @day 3/19/21
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['date','status','financial_type','financial_status','keyword']);
        $where['keywords_'] = $where['keyword'];
        unset($where['keyword']);
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->getAdminList($where,$page,$limit);
        return app('json')->success($data);
    }

    /**
     * TODO 取消申请
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 3/19/21
     */
    public function delete($id)
    {
        $this->repository->cancel($this->request->merId(),$id,['is_del' => 1]);
        return app('json')->success('取消申请');
    }

    /**
     * TODO
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 3/19/21
     */
    public function detail($id)
    {
        $data = $this->repository->detail($id,$this->request->merId());
        if(!$data)  return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }


    public function markForm($id)
    {
        return app('json')->success(formToData($this->repository->markForm($id)));
    }

    public function mark($id)
    {
        $ret = $this->repository->getWhere([$this->repository->getPk() => $id,'mer_id' => $this->request->merId()]);

        if(!$ret) return app('json')->fail('数据不存在');
        $data = $this->request->params(['mark']);
        $this->repository->update($id,$data);

        return app('json')->success('备注成功');
    }

    public function export()
    {
        $where = $this->request->params(['date','status','financial_type','financial_status','keyword']);
        $where['keywords_'] = $where['keyword'];
        unset($where['keyword']);
        $where['mer_id'] = $this->request->merId();

        [$page, $limit] = $this->getPage();
        $data = app()->make(ExcelService::class)->financialLog($where,$page,$limit);
        return app('json')->success($data);

    }
}
