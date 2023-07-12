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

namespace app\controller\admin\points;

use app\common\repositories\store\product\ProductRepository;
use app\validate\merchant\StoreProductValidate;
use think\App;
use crmeb\basic\BaseController;
use app\validate\merchant\StoreProductAdminValidate as validate;
use app\common\repositories\store\pionts\PointsProductRepository;

class Product extends BaseController
{
    /**
     * @var PointsProductRepository
     */
    protected $repository;


    /**
     * StoreProduct constructor.
     * @param App $app
     * @param PointsProductRepository $repository
     */
    public function __construct(App $app, PointsProductRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['cate_id', 'keyword', 'is_used', 'date','store_name']);
        return app('json')->success($this->repository->getAdminList(0, $where, $page, $limit));
    }


    public function create()
    {
        $data = $this->checkParams();
        $this->repository->create($data, $this->repository::PRODUCT_TYPE_POINTS);
        return app('json')->success('添加成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @return mixed
     */
    public function getStatusFilter()
    {
        return app('json')->success($this->repository->getFilter(null,'商品',0));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        return app('json')->success($this->repository->detail($id));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param $id
     * @param validate $validate
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->checkParams();
        $this->repository->edit($id,$data,$this->request->merId(),$this->repository::PRODUCT_TYPE_POINTS);
        return app('json')->success('编辑成功');
    }

    /**
     * TODO 上 / 下架
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param int $id
     * @return mixed
     */
    public function switchStatus($id)
    {
        if(!$this->repository->merExists(null,$id))
            return app('json')->fail('数据不存在');
        $status = $this->request->param('status',0) == 1 ? 1 : 0;
        $this->repository->switchShow($id,$status,'is_used');
        return app('json')->success('修改成功');
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/18
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if(!$this->repository->merExists($this->request->merId(),$id))
            return app('json')->fail('数据不存在');
        $this->repository->destory($id);
        return app('json')->success('删除成功');
    }
    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param validate $validate
     * @return array
     */
    public function checkParams()
    {
        $data = $this->request->params($this->repository::CREATE_PARAMS);
        $data['mer_status'] = 1;
        $data['delivery_way']  = [2];
        $data['delivery_free']  = 1;
        $data['is_used'] = $this->request->param('is_used',0);
        $data['is_hot'] = $this->request->param('is_hot',0);
        $data['is_show'] = 1;
        $data['status'] = 1;
        $data['pay_limit'] = 2;
        $data['product_type'] = $this->repository::PRODUCT_TYPE_POINTS;
        app()->make(StoreProductValidate::class)->check($data);
        return $data;
    }

    /**
     * TODO
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 3/17/21
     */
    public function updateSort($id)
    {
        $sort = $this->request->param('sort');
        $this->repository->updateSort($id,null,['rank' => $sort]);
        return app('json')->success('修改成功');
    }


    /**
     * TODO 批量显示隐藏
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/14
     */
    public function batchShow()
    {
        $ids = $this->request->param('ids');
        $status = $this->request->param('status') == 1 ? 1 : 0;
        $this->repository->batchSwitchShow($ids,$status,'is_used',0);
        return app('json')->success('修改成功');
    }

    public function isFormatAttr($id)
    {
        $data = $this->request->params([
            ['attrs', []],
            ['items', []],
            ['product_type', 0]
        ]);
        $data = app()->make(ProductRepository::class)->isFormatAttr($data['attrs'],$id,$data['product_type']);
        return app('json')->success($data);
    }

    public function preview()
    {
        $data = $this->request->param();
        return app('json')->success($this->repository->preview($data));
    }


}
