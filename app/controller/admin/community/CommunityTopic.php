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

namespace app\controller\admin\community;

use app\common\repositories\community\CommunityCategoryRepository;
use app\validate\admin\CommunityTopicValidate;
use crmeb\basic\BaseController;
use think\App;
use app\common\repositories\community\CommunityTopicRepository as repository;

class CommunityTopic extends BaseController
{
    /**
     * @var CommunityTopicRepository
     */
    protected $repository;

    /**
     * User constructor.
     * @param App $app
     * @param  $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function lst()
    {
        $where = $this->request->params(['topic_name', 'category_id', 'status','is_hot','is_del']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 10/26/21
     */
    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form(null)));
    }

    public function create()
    {
        $data = $this->checkParams();
        $data['topic_name'] = trim($data['topic_name']);
        if ($this->repository->fieldExists('topic_name', $data['topic_name'],null))
            return app('json')->fail('话题重复');
        $this->repository->create($data);
        app()->make(CommunityCategoryRepository::class)->clearCahe();
        return app('json')->success('添加成功');
    }

    public function updateForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->form($id)));
    }

    public function update($id)
    {
        $data = $this->checkParams();

        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        if ($this->repository->fieldExists('topic_name', $data['topic_name'],$id))
            return app('json')->fail('话题重复');
        $this->repository->update($id,$data);
        app()->make(CommunityCategoryRepository::class)->clearCahe();
        return app('json')->success('编辑成功');
    }


    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id,['is_del' => 1]);
        app()->make(CommunityCategoryRepository::class)->clearCahe();
        return app('json')->success('删除成功');
    }

    public function switchStatus($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id,['status' => $status]);
        app()->make(CommunityCategoryRepository::class)->clearCahe();
        return app('json')->success('修改成功');
    }

    public function checkParams()
    {
        $data = $this->request->params(['category_id','topic_name','is_hot','status','sort','pic']);
        app()->make(CommunityTopicValidate::class)->check($data);
        return $data;
    }

    public function getOptions()
    {
        return app('json')->success($this->repository->options());
    }

    public function switchHot($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id,['is_hot' => $status]);
        app()->make(CommunityCategoryRepository::class)->clearCahe();
        return app('json')->success('修改成功');
    }

}
