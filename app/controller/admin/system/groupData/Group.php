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
use app\common\repositories\system\groupData\GroupRepository;
use app\validate\admin\GroupValidate;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class Group
 * @package app\controller\admin\system\groupData
 * @author xaboy
 * @day 2020-03-27
 */
class Group extends BaseController
{
    /**
     * @var GroupRepository
     */
    private $repository;


    /**
     * GroupData constructor.
     * @param App $app
     * @param GroupRepository $repository
     */
    public function __construct(App $app, GroupRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    /**
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-03-27
     */
    public function get( $id)
    {
        $data = $this->repository->get($id)->hidden(['create_time', 'sort']);
        if (!$data)
            return app('json')->fail('数据组不存在');
        else
            return app('json')->success($data);

    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-01
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $data = $this->repository->page($page, $limit);

        return app('json')->success($data);
    }


    /**
     * @param GroupValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020-03-27
     */
    public function create(GroupValidate $validate)
    {
        $data = $this->request->params(['group_name', 'group_info', 'user_type', 'group_key', ['fields', []], ['sort', 0]]);
        $validate->check($data);
        if ($this->repository->keyExists($data['group_key']))
            return app('json')->fail('数据组key已存在');
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    /**
     * @return mixed
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-02
     */
    public function createTable()
    {
        return app('json')->success(formToData($this->repository->form()));
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-02
     */
    public function updateTable($id)
    {
        if (!$this->repository->exists($id)) app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    /**
     * @param int $id
     * @param GroupValidate $validate
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-03-27
     */
    public function update($id, GroupValidate $validate)
    {
        $data = $this->request->params(['group_name', 'group_info', 'user_type', 'group_key', ['fields', []], ['sort', 0]]);
        $validate->check($data);

        if (!$this->repository->exists($id))
            return app('json')->fail('数据组不存在');
        if ($this->repository->keyExists($data['group_key'], $id))
            return app('json')->fail('数据组key已存在');
        $this->repository->update($id, $data);
        return app('json')->success('修改成功');
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-03-27
     */
    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据组不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }


}
