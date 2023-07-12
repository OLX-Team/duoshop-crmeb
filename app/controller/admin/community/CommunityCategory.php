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

namespace app\controller\admin\community;

use crmeb\basic\BaseController;
use crmeb\traits\CategoresRepository;
use think\App;
use app\validate\admin\StoreCategoryValidate;
use app\common\repositories\community\CommunityCategoryRepository as repository;

class CommunityCategory extends BaseController
{
    /**
     * @var CommunityCategoryRepository
     */
    protected $repository;

    /**
     * User constructor.
     * @param App $app
     * @param  $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function lst()
    {
        $where = $this->request->params(['cate_name']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 10/26/21
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form(null)));
    }

    public function create()
    {
        $data = $this->checkParams();
        $data['cate_name'] = trim($data['cate_name']);
        if ($this->repository->fieldExists('cate_name', $data['cate_name'],null))
            return app('json')->fail('分类名重复');
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * TODO
     * @param $id
     * @return \think\response\Json
     * @author Qinii
     * @day 10/26/21
     */
    public function updateForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->clearCahe();
        return app('json')->success(formToData($this->repository->form($id)));
    }

    public function update($id)
    {
        $data = $this->checkParams();

        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        if ($this->repository->fieldExists('cate_name', $data['cate_name'],$id))
            return app('json')->fail('分类名重复');
        $this->repository->update($id,$data);
        $this->repository->clearCahe();
        return app('json')->success('编辑成功');
    }


    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        $this->repository->clearCahe();
        return app('json')->success('删除成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id,['is_show' => $status]);
        $this->repository->clearCahe();
        return app('json')->success('修改成功');
    }

    public function checkParams()
    {
        $data = $this->request->params(['pid','cate_name','is_show','sort']);
        $data['pid'] = 0;
        app()->make(StoreCategoryValidate::class)->check($data);
        return $data;
    }

    public function getOptions()
    {
        return app('json')->success($this->repository->options());
    }
}
