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

use app\common\repositories\store\product\ProductGroupBuyingRepository;
use app\common\repositories\store\product\ProductGroupUserRepository;
use crmeb\basic\BaseController;
use think\App;

class StoreProductGroupBuying extends BaseController
{
    protected $repository;

    /**
     * Product constructor.
     * @param App $app
     * @param ProductGroupBuyingRepository $repository
     */
    public function __construct(App $app, ProductGroupBuyingRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    /**
     * TODO 团列表
     * @return \think\response\Json
     * @author Qinii
     * @day 1/12/21
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','status','date','mer_id','is_trader','user_name']);
        $data = $this->repository->getAdminList($where,$page,$limit);
        return app('json')->success($data);
    }

    /**
     * TODO 团成员列表
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 1/12/21
     */
    public function detail($id)
    {
        [$page, $limit] = $this->getPage();
        if(!$this->repository->get($id))
            return app('json')->fail('数据不存在');
        $where = ['group_buying_id' => $id];
        $list = app()->make(ProductGroupUserRepository::class)->getAdminList($where,$page,$limit);
        return app('json')->success($list);
    }
}
