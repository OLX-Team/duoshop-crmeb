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


namespace app\controller\admin\system\admin;


use crmeb\basic\BaseController;
use app\common\repositories\system\admin\AdminLogRepository;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class AdminLog extends BaseController
{
    protected $repository;

    public function __construct(App $app, AdminLogRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-16
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['section_startTime', 'section_endTime', 'admin_id', 'method', 'date']);
        return app('json')->success($this->repository->lst($this->request->merId(), $where, $page, $limit));
    }
}
