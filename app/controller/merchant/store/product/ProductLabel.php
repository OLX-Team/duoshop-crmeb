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

use app\common\repositories\store\product\ProductLabelRepository;
use app\validate\admin\ProductLabelValidate;
use think\App;
use crmeb\basic\BaseController;

class ProductLabel extends BaseController
{

    protected $repository;

    public function __construct(App $app, ProductLabelRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['name', 'type', 'status']);
        $where['mer_id'] = $this->request->merId();
        $data  = $this->repository->getList($where,$page, $limit);
        return app('json')->success($data);
    }

    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form(null, 'merchantStoreProductLabelCreate')));
    }

    public function create(ProductLabelValidate $validate)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->check($data['label_name'], $this->request->merId()))
            return app('json')->fail('名称重复');
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->updateForm($id, 'merchantStoreProductLabelUpdate', $this->request->merId())));
    }

    public function update($id, ProductLabelValidate $validate)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->check($data['label_name'], $this->request->merId(),$id))
            return app('json')->fail('名称重复');
        $getOne = $this->repository->getWhere(['product_label_id' => $id,'mer_id' => $this->request->merId()]);
        if (!$getOne) return app('json')->fail('数据不存在');
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    public function detail($id)
    {
        $getOne = $this->repository->getWhere(['product_label_id' => $id,'mer_id' => $this->request->merId(), 'is_del' => 0]);
        if (!$getOne) return app('json')->fail('数据不存在');
        return  app('json')->success($getOne);
    }

    public function delete($id)
    {
        $getOne = $this->repository->getWhere(['product_label_id' => $id,'mer_id' => $this->request->merId()]);
        if (!$getOne) return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        return  app('json')->success('删除成功');
    }

    public function switchWithStatus($id)
    {
        $status = $this->request->param('status') == 1 ? 1 : 0;
        $getOne = $this->repository->getWhere(['product_label_id' => $id,'mer_id' => $this->request->merId()]);
        if (!$getOne) return app('json')->fail('数据不存在');
        $this->repository->update($id,['status' => $status]);
        return  app('json')->success('修改成功');
    }

    public function getOptions()
    {
        $data = $this->repository->getOptions($this->request->merId());
        return  app('json')->success($data);
    }

    public function checkParams(ProductLabelValidate $validate)
    {
        $params = ['label_name', 'status', 'sort', 'info'];
        $data = $this->request->params($params);
        $validate->check($data);
        return $data;
    }



}
