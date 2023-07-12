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


namespace app\controller\admin\wechat;


use crmeb\basic\BaseController;
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\services\WechatUserGroupService;
use crmeb\services\WechatUserTagService;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class WechatUser
 * @package app\controller\admin\wechat
 * @author xaboy
 * @day 2020-04-29
 */
class WechatUser extends BaseController
{
    /**
     * @var WechatUserRepository
     */
    protected $repository;

    /**
     * WechatUser constructor.
     * @param App $app
     * @param WechatUserRepository $repository
     */
    public function __construct(App $app, WechatUserRepository $repository)
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
     * @day 2020-04-29
     */
    public function lst()
    {
        $where = $this->request->params([
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['tagid_list', ''],
            ['groupid', '-1'],
            ['sex', ''],
            ['export', ''],
            ['subscribe', '']
        ]);
        $where['tagid_list'] = implode(',', array_filter(array_unique(explode(',', $where['tagid_list']))));
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * @param $id
     * @param WechatUserTagService $service
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-29
     */
    public function syncTag($id, WechatUserTagService $service)
    {
        $user = $this->repository->get($id);
        if (!$user)
            return app('json')->fail('数据不存在');
        $tag = $service->userTags($user->openid);
        if ($tag['tagid_list']) $data['tagid_list'] = implode(',', $tag['tagid_list']);
        else $data['tagid_list'] = '';
        $user->save($tag);
        return app('json')->success('同步成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-29
     */
    public function tagForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateUserTagForm($id)));
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-29
     */
    public function tag($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $tags = explode(',', $this->request->param('tag_id'));
        $this->repository->updateTag($id, $tags);
        return app('json')->success('操作成功');
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-29
     */
    public function groupForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateUserGroupForm($id)));
    }

    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-29
     */
    public function group($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $groupId = $this->request->param('group_id');
        $this->repository->updateGroup($id, $groupId);
        return app('json')->success('操作成功');
    }

    /**
     * @param WechatUserTagService $wechatUserTagService
     * @param WechatUserGroupService $wechatUserGroupService
     * @return mixed
     * @author xaboy
     * @day 2020-04-29
     */
    public function tagGroup(WechatUserTagService $wechatUserTagService, WechatUserGroupService $wechatUserGroupService)
    {
        $groupList = $wechatUserGroupService->lst();
        $tagList = $wechatUserTagService->lst();
        return app('json')->success(compact('groupList', 'tagList'));
    }
}
