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

use crmeb\basic\BaseController;
use think\App;
use app\common\repositories\community\CommunityRepository as repository;

class Community extends BaseController
{
    /**
     * @var CommunityRepository
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

    public function title()
    {
        $where['is_del'] =  0;
        return app('json')->success($this->repository->title($where));
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function lst()
    {
        $where = $this->request->params(['keyword','status','username','category_id','topic_id','is_show','is_type']);
        $where['order'] =  'start';
        $where['is_del'] =  0;
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * @param $id
     * @return mixed
     * @author Qinii
     */
    public function detail($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->detail($id));
    }

    public function updateForm($id)
    {
        return app('json')->success(formToData($this->repository->form($id)));
    }

    public function showForm($id)
    {
        return app('json')->success(formToData($this->repository->showForm($id)));
    }

    public function update($id)
    {
        $data['start']  = $this->request->param('start',1);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id,$data);
        return app('json')->success('修改成功');
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
        $this->repository->destory($id);
        return app('json')->success('删除成功');
    }

    public function switchStatus($id)
    {
        $data = $this->request->params(['status', 'refusal']);

        if (!in_array($data['status'],[0,1,-1,-2]))
            return app('json')->fail('状态类型错误');

        $data['is_show'] = ($data['status'] == 1) ? : 0;

        if($data['status'] == -1 && empty($data['refusal']))
            return app('json')->fail('请填写拒绝理由');
        if($data['status'] == -2 && empty($data['refusal']))
            return app('json')->fail('请填写下架原因');
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        $this->repository->setStatus($id,$data);
        return app('json')->success('操作成功');
    }

    public function switchShow($id)
    {
        $status = $this->request->param('status', 0) == 1 ? 1 : 0;
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        $this->repository->update($id,['is_show' => $status]);
        return app('json')->success('修改成功');
    }

}
