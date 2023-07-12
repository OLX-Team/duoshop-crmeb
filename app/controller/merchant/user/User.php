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


namespace app\controller\merchant\user;

use crmeb\basic\BaseController;
use app\common\repositories\user\UserRepository;
use think\App;

class User extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getUserList()
    {
        $keyword = $this->request->param('keyword', '');
        if (!$keyword)
            return app('json')->fail('请输入关键字');
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->merList($keyword, $page, $limit));
    }
}
