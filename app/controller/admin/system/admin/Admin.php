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


namespace app\controller\admin\system\admin;


use app\common\repositories\system\auth\RoleRepository;
use crmeb\basic\BaseController;
use app\common\repositories\system\admin\AdminRepository;
use app\validate\admin\AdminEditValidate;
use app\validate\admin\AdminValidate;
use Exception;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\response\Json;

class Admin extends BaseController
{
    protected $repository;

    public function __construct(App $app, AdminRepository $repository)
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
     * @day 2020-04-09
     */
    public function getList()
    {
        $where = $this->request->params(['keyword', 'date', 'status']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
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
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['status' => $status == 1 ? 1 : 0]);
        return app('json')->success('编辑成功');
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
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    /**
     * @param int $id
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-09
     */
    public function passwordForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->passwordForm($id)));
    }

    /**
     * @param AdminValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-04-09
     */
    public function create(AdminValidate $validate)
    {
        $data = $this->request->params(['account', 'pwd', 'phone', 'againPassword', 'real_name', ['roles', []], ['status', 0]]);
        $validate->check($data);

        if ($data['pwd'] !== $data['againPassword'])
            return app('json')->fail('两次密码输入不一致');
        unset($data['againPassword']);
        if ($this->repository->fieldExists('account', $data['account']))
            return app('json')->fail('账号已存在');
        $data['pwd'] = $this->repository->passwordEncode($data['pwd']);

        $check = app()->make(RoleRepository::class)->checkRole($data['roles'],0);
        if (!$check ) {
            return app('json')->fail('未开启或者不存在的身份不能添加');
        }

        $this->repository->create($data);

        return app('json')->success('添加成功');
    }

    /**
     * @param int $id
     * @param AdminValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-09
     */
    public function update($id, AdminValidate $validate)
    {
        $data = $this->request->params(['account', 'real_name', 'phone', ['roles', []], ['status', 0]]);
        $validate->isUpdate()->check($data);
        if ($this->repository->fieldExists('account', $data['account'], $id))
            return app('json')->fail('账号已存在');

        $check = app()->make(RoleRepository::class)->checkRole($data['roles'],0);
        if (!$check ) {
            return app('json')->fail('未开启或者不存在的身份不能添加');
        }
        $this->repository->update($id, $data);

        return app('json')->success('编辑成功');
    }

    /**
     * @param int $id
     * @param AdminValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-09
     */
    public function password($id, AdminValidate $validate)
    {
        $data = $this->request->params(['pwd', 'againPassword']);
        $validate->isPassword()->check($data);

        if ($data['pwd'] !== $data['againPassword'])
            return app('json')->fail('两次密码输入不一致');
        if (!$this->repository->exists($id))
            return app('json')->fail('管理员不存在');
        $data['pwd'] = $this->repository->passwordEncode($data['pwd']);
        unset($data['againPassword']);
        $this->repository->update($id, $data);

        return app('json')->success('修改密码成功');
    }

    /**
     * 删除
     * @param int $id
     * @return Json
     * @throws Exception
     * @author 张先生
     * @date 2020-03-30
     */
    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['is_del' => 1]);
        return app('json')->success('删除成功');
    }

    /**
     * @param AdminEditValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-20
     */
    public function edit(AdminEditValidate $validate)
    {
        $data = $this->request->params(['real_name', 'phone']);
        $validate->check($data);
        $this->repository->update($this->request->adminId(), $data);
        return app('json')->success('修改成功');
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-20
     */
    public function editForm()
    {
        $adminInfo = $this->request->adminInfo();
        return app('json')->success(formToData($this->repository->editForm(['real_name' => $adminInfo->real_name, 'phone' => $adminInfo->phone,'admin_id' => $adminInfo->admin_id])));
    }

    /**
     * @param AdminValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-20
     */
    public function editPassword(AdminValidate $validate)
    {
        $data = $this->request->params(['pwd', 'againPassword']);
        $validate->isPassword()->check($data);

        if ($data['pwd'] !== $data['againPassword'])
            return app('json')->fail('两次密码输入不一致');
        $data['pwd'] = $this->repository->passwordEncode($data['pwd']);
        unset($data['againPassword']);
        $this->repository->update($this->request->adminId(), $data);

        return app('json')->success('修改密码成功');
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-20
     */
    public function editPasswordForm()
    {
        return app('json')->success(formToData($this->repository->passwordForm($this->request->adminId(), true)));
    }

}
