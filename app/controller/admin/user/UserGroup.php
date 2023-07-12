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


namespace app\controller\admin\user;


use crmeb\basic\BaseController;
use app\common\repositories\user\UserGroupRepository;
use app\validate\admin\UserGroupValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class UserGroup
 * @package app\controller\admin\user
 * @author xaboy
 * @day 2020-05-07
 */
class UserGroup extends BaseController
{
    /**
     * @var UserGroupRepository
     */
    protected $repository;

    /**
     * UserGroup constructor.
     * @param App $app
     * @param UserGroupRepository $repository
     */
    public function __construct(App $app, UserGroupRepository $repository)
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
     * @day 2020-05-07
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList([], $page, $limit));
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-07
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form()));
    }

    /**
     * @param UserGroupValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-05-07
     */
    public function create(UserGroupValidate $validate)
    {
        $data = $this->checkParams($validate);
        $this->repository->create($data);

        return app('json')->success('添加成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DbException
     * @throws FormBuilderException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-07
     */
    public function updateForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    /**
     * @param $id
     * @param UserGroupValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-07
     */
    public function update($id, UserGroupValidate $validate)
    {
        $data = $this->checkParams($validate);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $data);

        return app('json')->success('编辑成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-07
     */
    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);

        return app('json')->success('删除成功');
    }

    /**
     * @param UserGroupValidate $validate
     * @return array
     * @author xaboy
     * @day 2020-05-07
     */
    protected function checkParams(UserGroupValidate $validate)
    {
        $data = $this->request->params(['group_name']);
        $validate->check($data);
        return $data;
    }
}
