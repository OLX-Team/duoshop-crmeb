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

use app\common\repositories\store\product\ProductGroupBuyingRepository;
use app\common\repositories\store\product\ProductGroupUserRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductGroupRepository;

class StoreProductGroup extends BaseController
{
    protected $repository;
    protected $userInfo;

    /**
     * StoreProductPresell constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, ProductGroupRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params([['active_type',1],'store_category_id','star','mer_id']);
        return app('json')->success($this->repository->getApiList($where,$page, $limit));
    }

    public function detail($id)
    {
        $data = $this->repository->apiDetail($id, $this->userInfo);
        return app('json')->success($data);
    }

    public function groupBuying($id)
    {
        $make = app()->make(ProductGroupBuyingRepository::class);
        $data = $make->detail($id,$this->userInfo);
        if(!$data) return app('json')->fail('数据丢失');
        return app('json')->success($data);
    }

    public function userCount()
    {
        [$page, $limit] = $this->getPage();
        $data = app()->make(ProductGroupUserRepository::class)->getApiList([],$page,$limit);
        return app('json')->success($data);
    }

    public function category()
    {
        return app('json')->success($this->repository->getCategory());
    }

    /**
     * TODO 取消参团
     * @author Qinii
     * @day 1/13/21
     */
    public function cancel()
    {
        $data = (int)$this->request->param('group_buying_id');

        $make = app()->make(ProductGroupBuyingRepository::class);

        $make->cancelGroup($data,$this->userInfo);

        return app('json')->success('取消成功，订单金额将会原路退回');

    }
}
