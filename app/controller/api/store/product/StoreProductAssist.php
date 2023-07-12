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

namespace app\controller\api\store\product;

use app\common\model\store\product\ProductAssistUser;
use app\common\repositories\store\product\ProductAssistSetRepository;
use app\common\repositories\store\product\ProductAssistUserRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductAssistRepository;

class StoreProductAssist extends BaseController
{
    protected $repository;
    protected $userInfo;

    /**
     * StoreProductPresell constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, ProductAssistRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['type','star','mer_id']);
        return app('json')->success($this->repository->getApiList($where,$page, $limit));
    }

    public function userCount()
    {
        return app('json')->success($this->repository->getUserCount());
    }
}
