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


namespace app\common\repositories\system\auth;


//附件
use app\common\dao\system\menu\RoleDao;
use app\common\repositories\BaseRepository;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Route;


/**
 * Class BaseRepository
 * @package common\repositories
 * @mixin RoleDao
 */
class RoleRepository extends BaseRepository
{
    public function __construct(RoleDao $dao)
    {
        /**
         * @var RoleDao
         */
        $this->dao = $dao;
    }

    /**
     * @param int $merId
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-18
     */
    public function search(int $merId, array $where, $page, $limit)
    {
        $query = $this->dao->search($merId, $where);
        $count = $query->count();
        $list = $query->page($page, $limit)->hidden(['update_time'])->select();

        foreach ($list as $k => $role) {
            $list[$k]['rule_name'] = $role->ruleNames();
        }

        return compact('count', 'list');
    }

    /**
     * @param int $id
     * @param array $data
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-04-09
     */
    public function update(int $id, array $data)
    {
        if (isset($data['rules']))
            $data['rules'] = implode(',', $data['rules']);
        return $this->dao->update($id, $data);
    }

    /**
     * @param bool $is_mer
     * @param int|null $id
     * @param array $formData
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-18
     */
    public function form($merType = 0, ?int $id = null, array $formData = []): Form
    {
        if ($merType)
            $form = Elm::createForm(is_null($id) ? Route::buildUrl('merchantRoleCreate')->build() : Route::buildUrl('merchantRoleUpdate', ['id' => $id])->build());
        else
            $form = Elm::createForm(is_null($id) ? Route::buildUrl('systemRoleCreate')->build() : Route::buildUrl('systemRoleUpdate', ['id' => $id])->build());

        $options = app()->make(MenuRepository::class)->getTree($merType);

        $form->setRule([
            Elm::input('role_name', '身份名称')->required(),
            Elm::tree('rules', '权限')->data($options)->showCheckbox(true),
            Elm::switches('status', '是否开启', 1)->inactiveValue(0)->activeValue(1)->inactiveText('关')->activeText('开'),
        ]);

        return $form->setTitle(is_null($id) ? '添加身份' : '编辑身份')->formData($formData);
    }


    /**
     * @param bool $is_mer
     * @param int $id
     * @return Form
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-18
     */
    public function updateForm($is_mer, int $id)
    {
        return $this->form($is_mer, $id, $this->dao->get($id)->toArray());
    }

    public function checkRole(array $role, $merId)
    {
        $rest = $this->dao->search($merId, ['role_ids' => $role,'status' => 1])->column('role_id');
        sort($role);
        sort($rest);
        return (sort($role) == sort($rest)) ?  true: false;
    }
}
