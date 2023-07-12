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

use app\validate\admin\StoreBrandCategoryValidate as validate;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\StoreBrandCategoryRepository as repository;

class StoreBrandCategory extends BaseController
{

    protected $repository;

    /**
     * ArticleCategory constructor.
     * @param App $app
     * @param StoreBrandCategoryRepository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * 列表
     * @return mixed
     * @author Qinii
     */
    public function lst()
    {
        $status = $this->request->param('status',null);
        return app('json')->success($this->repository->getFormatList($this->request->merId(),$status));
    }

    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form($this->request->merId())));
    }

    public function updateForm($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($this->request->merId(),$id)));
    }

    public function create(validate $validate)
    {
        $data = $this->checkParams($validate);
        if ($data['pid'] && !$this->repository->merExists($this->request->merId(), $data['pid']))
            return app('json')->fail('上级分类不存在');
        if ($data['pid'] && !$this->repository->checkLevel($data['pid']))
            return app('json')->fail('不可添加更低阶分类');
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function update($id,validate $validate)
    {
        $data = $this->checkParams($validate);

        $brand = $this->repository->getWhere(['store_brand_category_id' => $id]);
        if (!$brand)
            return app('json')->fail('数据不存在');

        if ($data['pid'] && !$this->repository->merExists($this->request->merId(), $data['pid']))
            return app('json')->fail('上级分类不存在');

        if ($data['pid'] && !$this->repository->checkLevel($data['pid']))
            return app('json')->fail('不可添加更低阶分类');

        if (!$this->repository->checkChangeToChild($id, $data['pid']))
            return app('json')->fail('无法修改到当前分类到子集，请先修改子类');

        if ($brand['pid'] != $data['pid'] && !$this->repository->checkChildLevel($id, $data['pid']))
            return app('json')->fail('子类超过最低限制，请先修改子类');

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

    public function detail($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id));
    }
    /**
     * 验证
     * @param WechatNewsValidate $validate
     * @param bool $isCreate
     * @return array
     * @author Qinii
     */
    public function checkParams(validate $validate)
    {
        $data = $this->request->params(['pid','cate_name','is_show','sort']);
        $validate->check($data);
        return $data;
    }
}
