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

use app\common\repositories\store\product\StoreDiscountRepository;
use crmeb\basic\BaseController;
use think\App;

class Discounts extends BaseController
{

    protected  $repository ;

    /**
     * Product constructor.
     * @param App $app
     * @param StoreDiscountRepository $repository
     */
    public function __construct(App $app ,StoreDiscountRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword','store_name','mer_id','title','status','type']);
        $data = $this->repository->getAdminlist($where, $page, $limit);
        return app('json')->success($data);
    }

    public function detail($id)
    {
        $data = $this->repository->detail($id, 0);
        if (!$data )  return app('json')->fail('数据不存在');
        return app('json')->success($data);
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status') == 1 ?: 0;
        if (!$this->repository->getWhere([$this->repository->getPk() => $id]))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['status' => $status]);
        return app('json')->success('修改成功');
    }



}
