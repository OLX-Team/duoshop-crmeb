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

use app\common\repositories\user\UserBillRepository;
use crmeb\basic\BaseController;
use app\common\repositories\user\FeedbackRepository;
use think\App;

class Member extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserBillRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getMemberValue()
    {
        $where = [
            'uid' => $this->request->uid(),
            'category' => 'sys_members',
        ];
        $data = $this->repository->month($where);
        return app('json')->success($data);
    }
}
