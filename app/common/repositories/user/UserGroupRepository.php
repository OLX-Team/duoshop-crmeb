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


namespace app\common\repositories\user;


use app\common\dao\user\UserGroupDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;

/**
 * Class UserGroupRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020-05-07
 * @mixin UserGroupDao
 */
class UserGroupRepository extends BaseRepository
{
    /**
     * @var UserGroupDao
     */
    protected $dao;

    /**
     * UserGroupRepository constructor.
     * @param UserGroupDao $dao
     */
    public function __construct(UserGroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-07
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count($this->dao->getPk());
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * @param null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-05-07
     */
    public function form($id = null, array $formData = [])
    {
        $isCreate = is_null($id);
        $action = Route::buildUrl($isCreate ? 'systemUserGroupCreate' : 'systemUserGroupUpdate', $isCreate ? [] : compact('id'))->build();
        return Elm::createForm($action, [
            Elm::input('group_name', '用户分组名称')->required()
        ])->setTitle($isCreate ? '添加用户分组' : '编辑用户分组')->formData($formData);
    }

    /**
     * @param $id
     * @return Form
     * @throws FormBuilderException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-07
     */
    public function updateForm($id)
    {
        return $this->form($id, $this->dao->get($id)->toArray());
    }
}
