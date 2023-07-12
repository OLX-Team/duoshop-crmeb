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


namespace app\controller\admin\article;


use crmeb\basic\BaseController;
use app\common\repositories\article\ArticleCategoryRepository;
use app\validate\admin\ArticleCategoryValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class ArticleCategory
 * @package app\controller\admin\article
 * @author xaboy
 * @day 2020-04-20
 */
class ArticleCategory extends BaseController
{
    /**
     * @var ArticleCategoryRepository
     */
    protected $repository;

    /**
     * ArticleCategory constructor.
     * @param App $app
     * @param ArticleCategoryRepository $repository
     */
    public function __construct(App $app, ArticleCategoryRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-20
     */
    public function lst()
    {
        $result = $this->repository->getFormatList();
        return app('json')->success($result);
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-15
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form(0)));
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-15
     */
    public function updateForm($id)
    {
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm(0, $id)));
    }

    /**
     * @param ArticleCategoryValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-04-20
     */
    public function create(ArticleCategoryValidate $validate)
    {
        $data = $this->checkParams($validate);
        if ($data['pid'] && !$this->repository->merExists(0, $data['pid']))
            return app('json')->fail('上级分类不存在');
        $data['mer_id'] = 0;
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @param $id
     * @param ArticleCategoryValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-20
     */
    public function update($id, ArticleCategoryValidate $validate)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        if ($data['pid'] && !$this->repository->merExists(0, $data['pid']))
            return app('json')->fail('上级分类不存在');

        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-20
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->exists($id))
            return app('json')->fail('分类不存在');
        $this->repository->update($id, compact('status'));
        return app('json')->success('修改成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-20
     */
    public function delete($id)
    {
        if ($this->repository->merFieldExists(0, 'pid', $id))
            return app('json')->fail('存在子级,无法删除');
        $this->repository->delete($id, 0);
        return app('json')->success('删除成功');
    }


    /**
     * 详情
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author Qinii
     */
    public function detail($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('分类不存在');
        return app('json')->success($this->repository->get($id,0));
    }

    /**
     * @param ArticleCategoryValidate $validate
     * @return array
     * @author xaboy
     * @day 2020-04-20
     */
    public function checkParams(ArticleCategoryValidate $validate)
    {
        $data = $this->request->params([['pid', 0], 'title', 'info', 'status', 'image', 'sort']);
        $validate->check($data);
        return $data;
    }

    public function select()
    {
        $result = $this->repository->getFormatList(0,1);
        return app('json')->success($result);
    }
}
