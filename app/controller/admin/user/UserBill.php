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


use app\common\repositories\store\ExcelRepository;
use crmeb\basic\BaseController;
use app\common\repositories\user\UserBillRepository;
use crmeb\services\ExcelService;
use think\App;

class UserBill extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserBillRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getList()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'date', 'type']);
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function type()
    {
        return app('json')->success($this->repository->type());
    }


    public function export()
    {
        $where = $this->request->params(['keyword', 'date', 'type']);
        [$page, $limit] = $this->getPage();
        $data = app()->make(ExcelService::class)->bill($where,$page,$limit);
        return app('json')->success($data);
    }
}
