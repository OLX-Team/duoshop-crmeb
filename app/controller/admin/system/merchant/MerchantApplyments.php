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


namespace app\controller\admin\system\merchant;

use app\common\repositories\system\CacheRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantApplymentsRepository;

class MerchantApplyments extends BaseController
{
    protected $repository;

    /**
     * MerchantApplyments constructor.
     * @param App $app
     * @param MerchantApplymentsRepository $repository
     */
    public function __construct(App $app, MerchantApplymentsRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['mer_name','status','date','mer_applyments_id','out_request_no','applyment_id','mer_id']);
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id);
        if(empty($data)) return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }

    public function switchWithStatus($id)
    {
        $data = $this->request->params(['status','message']);

        if(!in_array($data['status'],[0,-1,10])) return app('json')->fail('参数错误');
        if($data['status'] == -1 && !$data['message'] )  return app('json')->fail('驳回理由为空');
        $this->repository->switchWithStatus($id,$data);
        return app('json')->success('审核成功');
    }

    public function getMerchant($id)
    {
        $data = $this->repository->getMerchant($id);
        return app('json')->success($data);
    }

    public function markForm($id)
    {
        return app('json')->success(formToData($this->repository->markForm($id)));
    }

    public function mark($id)
    {
        if(!$this->repository->get($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id,['mark' => $this->request->param('mark','')]);

        return app('json')->success('备注成功');
    }

}
