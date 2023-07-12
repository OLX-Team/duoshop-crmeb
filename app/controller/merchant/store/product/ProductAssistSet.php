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

namespace app\controller\merchant\store\product;

use app\common\repositories\store\product\ProductAssistSetRepository as repository;
use app\common\repositories\store\product\ProductAssistUserRepository;
use crmeb\basic\BaseController;
use think\App;
use app\validate\merchant\StoreProductAssistValidate;

class ProductAssistSet extends BaseController
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
        $where = $this->request->params(['keyword','status','type','date','user_name']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getMerchantList($where,$page,$limit));
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
        if(!$this->repository->getWhere(['product_assist_set_id' => $id,'mer_id' => $this->request->merId()]))
            return app('json')->fail('数据不存在');
        $make = app()->make(ProductAssistUserRepository::class);
        return app('json')->success($make->userList($where,$page,$limit));
    }



}
