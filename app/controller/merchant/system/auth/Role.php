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


namespace app\controller\merchant\system\auth;


use crmeb\basic\BaseController;
use app\common\repositories\system\auth\RoleRepository;
use app\validate\admin\RoleValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

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
     * @day 2020-04-18
     */
    public function createForm()
    {
        $merchant = $this->request->merchant();
        return app('json')->success(formToData($this->repository->form((int)$merchant->type_id)));
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-18
     */
    public function updateForm($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $merchant = $this->request->merchant();
        return app('json')->success(formToData($this->repository->updateForm((int)$merchant->type_id, $id)));
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-18
     */
    public function getList()
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->search($this->request->merId(), [], $page, $limit));
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
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['status' => $status == 1 ? 1 : 0]);
        return app('json')->success('编辑成功');
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-18
     */
    public function delete($id)
    {
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    /**
     * @param RoleValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-04-18
     */
    public function create(RoleValidate $validate)
    {
        $data = $this->checkParam($validate);
        $data['mer_id'] = $this->request->merId();

        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @param int $id
     * @param RoleValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-18
     */
    public function update($id, RoleValidate $validate)
    {
        $data = $this->checkParam($validate);
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $data);
        return app('json')->success('编辑成功');
    }

    /**
     * @param RoleValidate $validate
     * @return array
     * @author xaboy
     * @day 2020-04-18
     */
    private function checkParam(RoleValidate $validate)
    {
        $data = $this->request->params(['role_name', ['rules', []], ['status', 0]]);
        $validate->check($data);
        return $data;
    }
}
