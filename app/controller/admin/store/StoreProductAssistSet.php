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

namespace app\controller\admin\store;

use app\common\repositories\store\product\ProductAssistSetRepository as repository;
use app\common\repositories\store\product\ProductAssistUserRepository;
use app\common\repositories\system\CacheRepository;
use crmeb\basic\BaseController;
use think\App;
use app\validate\merchant\StoreProductPresellValidate;

class StoreProductAssistSet extends BaseController
{
    protected  $repository ;

    /**
     * Product constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app ,repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * TODO 列表
     * @return mixed
     * @author Qinii
     * @day 2020-10-12
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','status','type','date','user_name','mer_id','is_trader']);
        return app('json')->success($this->repository->getAdminList($where,$page,$limit));
    }


    /**
     * TODO 详情
     * @param $id
     * @return mixed
     * @author Qinii
     * @day 2020-10-12
     */
    public function detail($id)
    {
        [$page, $limit] = $this->getPage();
        $where['product_assist_set_id'] = $id;
        $make = app()->make(ProductAssistUserRepository::class);
        return app('json')->success($make->userList($where,$page,$limit));
    }


}
