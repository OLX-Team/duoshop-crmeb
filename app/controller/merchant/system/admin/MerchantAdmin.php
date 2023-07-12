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


namespace app\controller\merchant\system\admin;


use app\common\repositories\system\auth\RoleRepository;
use crmeb\basic\BaseController;
use app\common\repositories\system\merchant\MerchantAdminRepository;
use app\validate\admin\AdminEditValidate;
use app\validate\admin\AdminValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class MerchantAdmin
 * @package app\controller\admin\system\admin
 * @author xaboy
 * @day 2020-04-18
 */
class MerchantAdmin extends BaseController
{
    /**
     * @var MerchantAdminRepository
     */
    protected $repository;

    /**
     * @var int
     */
    protected $merId;

    /**
     * MerchantAdmin constructor.
     * @param App $app
     * @param MerchantAdminRepository $repository
     */
    public function __construct(App $app, MerchantAdminRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->merId = $this->request->merId();
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
        $where = $this->request->params(['keyword', 'date', 'status']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($this->merId, $where, $page, $limit));
    }


    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-18
     */
    public function switchStatus($id)
    {
        $status = $this->request->param('status');
        if (!$this->repository->exists($id, $this->merId, 1))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, ['status' => $status == 1 ? 1 : 0]);
        return app('json')->success('编辑成功');
    }


    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-18
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form($this->merId)));
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
        if (!$this->repository->exists($id, $this->merId, 1))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($this->merId, $id)));
    }


    /**
     * @param int $id
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-18
     */
    public function passwordForm($id)
    {
        if (!$this->repository->exists($id, $this->merId))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->passwordForm($id)));
    }


    /**
     * @param AdminValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-04-18
     */
    public function create(AdminValidate $validate)
    {
        $data = $this->request->params(['account', 'phone', 'pwd', 'againPassword', 'real_name', ['roles', []], ['status', 0]]);
        $validate->check($data);

        if ($data['pwd'] !== $data['againPassword'])
            return app('json')->fail('两次密码输入不一致');
        unset($data['againPassword']);
        if ($this->repository->merFieldExists($this->merId, 'account', $data['account']))
            return app('json')->fail('账号已存在');
        $data['pwd'] = $this->repository->passwordEncode($data['pwd']);
        $data['mer_id'] = $this->merId;
        $data['level'] = 1;
        $check = app()->make(RoleRepository::class)->checkRole($data['roles'],$this->merId);
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
     * @day 2020-04-18
     */
    public function update($id, AdminValidate $validate)
    {
        $data = $this->request->params(['account', 'phone', 'real_name', ['roles', []], ['status', 0]]);
        $validate->isUpdate()->check($data);
        if ($this->repository->merFieldExists($this->merId, 'account', $data['account'], $id))
            return app('json')->fail('账号已存在');

        $check = app()->make(RoleRepository::class)->checkRole($data['roles'],$this->merId);
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
     * @day 2020-04-18
     */
    public function password($id, AdminValidate $validate)
    {
        $data = $this->request->params(['pwd', 'againPassword']);
        $validate->isPassword()->check($data);

        if ($data['pwd'] !== $data['againPassword'])
            return app('json')->fail('两次密码输入不一致');
        if (!$this->repository->exists($id, $this->merId))
            return app('json')->fail('管理员不存在');
        $data['pwd'] = $this->repository->passwordEncode($data['pwd']);
        unset($data['againPassword']);
        $this->repository->update($id, $data);

        return app('json')->success('修改密码成功');
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
        if (!$this->repository->exists($id, $this->merId, 1))
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
        return app('json')->success(formToData($this->repository->editForm(['real_name' => $adminInfo->real_name, 'phone' => $adminInfo->phone])));
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
        return app('json')->success(formToData($this->repository->passwordForm($this->request->adminId(), 3)));
    }

}
