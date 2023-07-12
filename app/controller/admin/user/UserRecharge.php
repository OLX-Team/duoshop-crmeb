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


namespace app\controller\admin\user;


use crmeb\basic\BaseController;
use app\common\repositories\user\UserRechargeRepository;
use think\App;

class UserRecharge extends BaseController
{

    protected $repository;

    public function __construct(App $app, UserRechargeRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getList()
    {
        $where = $this->request->params(['date', 'paid', 'keyword']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function total()
    {
        $totalRefundPrice = $this->repository->totalRefundPrice();
        $totalPayPrice = $this->repository->totalPayPrice();
        $totalRoutinePrice = $this->repository->totalRoutinePrice();
        $totalWxPrice = $this->repository->totalWxPrice();
        return app('json')->success(compact('totalWxPrice', 'totalRoutinePrice', 'totalPayPrice', 'totalRefundPrice'));
    }
}
