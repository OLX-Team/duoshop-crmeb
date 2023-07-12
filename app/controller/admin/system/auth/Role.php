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


namespace app\controller\admin\system\auth;


use crmeb\basic\BaseController;
use app\common\repositories\system\auth\RoleRepository;
use app\validate\admin\RoleValidate;
use Exception;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\response\Json;

class Role extends BaseController
{
    protected $repository;

    public function __construct(App $app, RoleRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-09
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form()));
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-09
     */
    public function updateForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm(false, $id)));
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-09
     */
    public function getList()
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->search(0, [], $page, $limit));
    }

    /**
     * 获取单个
     * @param int $id
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 张先生
     * @date 2020-03-26
     */
    public function get( $id)
    {
        $data = $this->repository->get($id);
        return app('json')->success($data);
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-09
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status');
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['status' => $status == 1 ? 1 : 0]);
        return app('json')->success('编辑成功');
    }

    /**
     * 删除单个
     * @param int $id
     * @return Json
     * @throws Exception
     * @author 张先生
     * @date 2020-03-30
     */
    public function delete($id)
    {
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    /**
     * 创建
     * @param RoleValidate $validate
     * @return mixed
     * @author 张先生
     * @date 2020-03-30
     */
    public function create(RoleValidate $validate)
    {
        $data = $this->checkParam($validate);
        $data['mer_id'] = 0;
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * 更新
     * @param int $id id
     * @param RoleValidate $validate
     * @return mixed
     * @throws DbException
     * @author 张先生
     * @date 2020-03-30
     */
    public function update($id, RoleValidate $validate)
    {
        $data = $this->checkParam($validate);
        if (!$this->repository->merExists(0, $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * 添加和修改参数验证
     * @param RoleValidate $validate 验证规则
     * @return mixed
     * @author 张先生
     * @date 2020-03-30
     */
    private function checkParam(RoleValidate $validate)
    {
        $data = $this->request->params(['role_name', ['rules', []], ['status', 0]]);
        $validate->check($data);
        return $data;
    }
}
