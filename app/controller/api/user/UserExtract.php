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

use crmeb\basic\BaseController;
use app\common\repositories\system\groupData\GroupDataRepository;
use think\App;
use app\validate\api\UserExtractValidate as validate;
use app\common\repositories\user\UserExtractRepository as repository;

class UserExtract extends BaseController
{
    /**
     * @var repository
     */
    public $repository;

    /**
     * UserExtract constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app,repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page,$limit] = $this->getPage();
        $where = $this->request->params(['status']);
        [$start,$stop]= $this->request->params(['start','stop'],true);
        $where['date'] = $start&&$stop ? date('Y/m/d',$start).'-'.date('Y/m/d',$stop) : '';
        $where['uid'] = $this->request->uid();
        return app('json')->success($this->repository->getList($where,$page,$limit));
    }

    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        $user = $this->request->userInfo();
        if($data['extract_type'] == 3 && !systemConfig('sys_extension_type') ) return app('json')->fail('未开启付款到零钱');
        $this->repository->create($user,$data);
        return app('json')->success('申请已提交');
    }

    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['extract_type','bank_code','bank_address','alipay_code','wechat','extract_pic','extract_price','real_name','bank_name']);
        $validate->check($data);
        return $data;
    }

    public function bankLst()
    {
        [$page,$limit] = $this->getPage();
        $data = app()->make(GroupDataRepository::class)->groupData('bank_list',0,$page,100);
        return app('json')->success($data);
    }

    public function historyBank()
    {
        $data = $this->repository->getHistoryBank($this->request->userInfo()->uid);
        return app('json')->success($data ?? []);
    }


}
