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

use app\common\repositories\system\CacheRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductPresellRepository;

class StoreProductPresell extends BaseController
{
    protected $repository;
    protected $userInfo;

    /**
     * StoreProductPresell constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, ProductPresellRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params([['type',4],'star','mer_id']);
        return app('json')->success($this->repository->getApiList($where,$page, $limit));
    }


    public function detail($id)
    {
        $data = $this->repository->apiDetail((int)$id,$this->userInfo);
        return  app('json')->success($data);
    }

    public function getAgree()
    {
        $make = app()->make(CacheRepository::class);
        return app('json')->success($make->getResult('sys_product_presell_agree'));
    }

}
