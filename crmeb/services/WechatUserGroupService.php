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


namespace crmeb\services;


use EasyWeChat\Support\Collection;
use EasyWeChat\User\Group;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\facade\Route;

/**
 * Class WechatUserGroupService
 * @package crmeb\services
 * @author xaboy
 * @day 2020-04-27
 */
class WechatUserGroupService
{
    /**
     * @var Group
     */
    protected $userGroup;

    /**
     * WechatTemplateService constructor.
     */
    public function __construct()
    {
        $this->userGroup = WechatService::create()->getApplication()->user_group;
    }

    /**
     * @return Group
     * @author xaboy
     * @day 2020-04-29
     */
    public function userGroup()
    {
        return $this->userGroup;
    }

    /**
     * @return array
     * @author xaboy
     * @day 2020-04-27
     */
    public function lst()
    {
        return $this->userGroup->lists()->toArray();
    }

    /**
     * @param $groupName
     * @return Collection
     * @author xaboy
     * @day 2020-04-27
     */
    public function create($groupName)
    {
        return $this->userGroup->create($groupName);
    }

    /**
     * @param $id
     * @param $groupName
     * @return Collection
     * @author xaboy
     * @day 2020-04-27
     */
    public function update($id, $groupName)
    {
        return $this->userGroup->update($id, $groupName);
    }

    /**
     * @param $id
     * @return Collection
     * @author xaboy
     * @day 2020-04-27
     */
    public function delete($id)
    {
        return $this->userGroup->delete($id);
    }


    /**
     * @param null $id
     * @param string $name
     * @return Form
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020-04-27
     */
    public function form($id = null, $name = '')
    {
        return Elm::createForm($id ? Route::buildUrl('updateWechatUserGroup', compact('id'))->build() : Route::buildUrl('createWechatUserGroup')->build(), [
            Elm::input('tag_name', '分组名称', $name)->required()
        ])->setTitle($id ? '编辑用户分组' : '添加用户分组');
    }
}
