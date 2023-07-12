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
use EasyWeChat\User\Tag;
use FormBuilder\Exception\FormBuilderException;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\facade\Route;

/**
 * Class WechatUserTagService
 * @package crmeb\services
 * @author xaboy
 * @day 2020-04-27
 */
class WechatUserTagService
{
    /**
     * @var Tag
     */
    protected $userTag;

    /**
     * WechatTemplateService constructor.
     */
    public function __construct()
    {
        $this->userTag = WechatService::create()->getApplication()->user_tag;
    }

    public function userTag()
    {
        return $this->userTag;
    }

    /**
     * @return array
     * @author xaboy
     * @day 2020-04-27
     */
    public function lst()
    {
        return $this->userTag->lists()->toArray();
    }

    /**
     * @param $tagName
     * @return Collection
     * @author xaboy
     * @day 2020-04-27
     */
    public function create($tagName)
    {
        return $this->userTag->create($tagName);
    }

    /**
     * @param $id
     * @param $tagName
     * @return Collection
     * @author xaboy
     * @day 2020-04-27
     */
    public function update($id, $tagName)
    {
        return $this->userTag->update($id, $tagName);
    }

    /**
     * @param $id
     * @return Collection
     * @author xaboy
     * @day 2020-04-27
     */
    public function delete($id)
    {
        return $this->userTag->delete($id);
    }

    /**
     * @param string $openId
     * @return array
     * @author xaboy
     * @day 2020-04-29
     */
    public function userTags(string $openId)
    {
        return $this->userTag->userTags($openId)->toArray();
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
        return Elm::createForm($id ? Route::buildUrl('updateWechatUserTag', compact('id'))->build() : Route::buildUrl('createWechatUserTag')->build(), [
            Elm::input('tag_name', '标签名称', $name)->required()
        ])->setTitle($id ? '编辑用户标签' : '添加用户标签');
    }
}
