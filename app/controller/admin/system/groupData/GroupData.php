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


namespace app\controller\admin\system\groupData;


use crmeb\basic\BaseController;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\groupData\GroupRepository;
use app\validate\admin\GroupDataValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class GroupData
 * @package app\controller\admin\system\groupData
 * @author xaboy
 * @day 2020-03-27
 */
class GroupData extends BaseController
{
    /**
     * @var GroupDataRepository
     */
    protected $repository;

    /**
     * GroupData constructor.
     * @param App $app
     * @param GroupDataRepository $repository
     */
    public function __construct(App $app, GroupDataRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @param int $groupId
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-30
     */
    public function lst($groupId)
    {
        [$page, $limit] = $this->getPage();
        $lst = $this->repository->getGroupDataLst($this->request->merId(), intval($groupId), $page, $limit);
        return app('json')->success($lst);
    }

    /**
     * @param int $groupId
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-02
     */
    public function createTable($groupId)
    {
        if (!app()->make(GroupRepository::class)->exists($groupId))
            return app('json')->fail('组合数据不存在!');
        return app('json')->success(formToData($this->repository->form($groupId, null, $this->request->merId())));
    }

    /**
     * @param $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-13
     */
    public function changeStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, compact('status'));
        return app('json')->success('修改成功');
    }

    /**
     * @param int $groupId
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-02
     */
    public function updateTable($groupId, $id)
    {
        if (!app()->make(GroupRepository::class)->exists($groupId))
            return app('json')->fail('组合数据不存在!');
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在!');
        return app('json')->success(formToData($this->repository->updateForm($groupId, $this->request->merId(), $id)));
    }

    /**
     * @param int $groupId
     * @param GroupDataValidate $validate
     * @param GroupRepository $groupRepository
     * @return mixed
     * @author xaboy
     * @day 2020-04-02
     */
    public function create($groupId, GroupDataValidate $validate, GroupRepository $groupRepository)
    {
        $data = $this->request->params([['sort', 0], ['status', 0]]);
        $validate->check($data);
        if (!$groupRepository->exists($groupId))
            return app('json')->fail('数据组不存在');
        $fieldRule = $groupRepository->fields($groupId);
        $data['value'] = $this->request->params(array_column($fieldRule, 'field'));
        $data['group_id'] = $groupId;
        $this->repository->create($this->request->merId(), $data, $fieldRule);
        return app('json')->success('添加成功');
    }

    /**
     * @param int $groupId
     * @param int $id
     * @param GroupDataValidate $validate
     * @param GroupRepository $groupRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-04-02
     */
    public function update($groupId, $id, GroupDataValidate $validate, GroupRepository $groupRepository)
    {
        $data = $this->request->params([['sort', 0], ['status', 0]]);
        $validate->check($data);
        if (!$groupRepository->exists($groupId))
            return app('json')->fail('数据组不存在');
        if (!$this->repository->merExists($this->request->merId(), $id))
            return app('json')->fail('数据不存在!');
        $fieldRule = $groupRepository->fields($groupId);
        $data['value'] = $this->request->params(array_column($fieldRule, 'field'));
        $this->repository->merUpdate($this->request->merId(), $id, $data, $fieldRule);

        return app('json')->success('修改成功');
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-03-30
     */
    public function delete($id)
    {
        $this->repository->merDelete($this->request->merId(), $id);
        return app('json')->success('删除成功');
    }
}
