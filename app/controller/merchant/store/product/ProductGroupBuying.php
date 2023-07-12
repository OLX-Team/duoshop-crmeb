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

use app\common\repositories\store\product\ProductGroupUserRepository;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductGroupBuyingRepository;

class ProductGroupBuying extends BaseController
{
    protected  $repository ;

    /**
     * ProductGroup constructor.
     * @param App $app
     * @param ProductGroupBuyingRepository $repository
     */
    public function __construct(App $app ,ProductGroupBuyingRepository $repository)
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
        $where = $this->request->params(['keyword','status','date','user_name']);
        $where['mer_id'] = $this->request->merId();
        $data = $this->repository->getMerchantList($where,$page,$limit);
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
