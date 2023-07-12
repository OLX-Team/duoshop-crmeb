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

use app\common\repositories\store\StoreCategoryRepository;
use app\validate\admin\StoreCategoryValidate as validate;
use think\App;
use crmeb\basic\BaseController;

class Category extends BaseController
{
    protected $repository;

    public function __construct(App $app, StoreCategoryRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        return app('json')->success($this->repository->getFormatList($this->request->merId(), null, 1));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @return mixed
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->pointsForm(null)));
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/11
     * @param validate $validate
     * @return mixed
     */
    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        $data['cate_name'] = trim($data['cate_name']);
        if ($data['cate_name'] == '') return app('json')->fail('分类名不可为空');
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->pointsForm($id)));
    }

    public function update($id, validate $validate)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->switchStatus($id, $status);
        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        if ($this->repository->hasChild($id))
            return app('json')->fail('该分类存在子集，请先处理子集');

        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    public function select()
    {
        return app('json')->success($this->repository->getAll(0,1,1));
    }
    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['cate_name','is_show','pic','sort','type',['pid',0]]);
        $validate->check($data);
        return $data;
    }

}
