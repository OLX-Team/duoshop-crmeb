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


use app\common\dao\user\UserLabelDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;

/**
 * Class UserLabelRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020-05-07
 * @mixin UserLabelDao
 */
class UserLabelRepository extends BaseRepository
{
    /**
     * @var UserLabelDao
     */
    protected $dao;

    /**
     * UserGroupRepository constructor.
     * @param UserLabelDao $dao
     */
    public function __construct(UserLabelDao $dao)
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
        $list = $query->order('label_id DESC')->page($page, $limit)->select();
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
        $action = Route::buildUrl($isCreate ? 'systemUserLabelCreate' : 'systemUserLabelUpdate', $isCreate ? [] : compact('id'))->build();
        return Elm::createForm($action, [
            Elm::input('label_name', '用户标签名称')->required()
        ])->setTitle($isCreate ? '添加用户标签' : '编辑用户标签')->formData($formData);
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
