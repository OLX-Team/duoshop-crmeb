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


namespace app\controller\merchant\store\broadcast;


use app\common\repositories\store\broadcast\BroadcastAssistantRepository;
use crmeb\basic\BaseController;
use think\App;
use think\exception\ValidateException;

class BroadcastAssistant extends BaseController
{
    protected $repository;

    public function __construct(App $app, BroadcastAssistantRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['username', 'nickname']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function createForm()
    {
        return app('json')->success(formToData($this->repository->form(null)));
    }

    public function updateForm($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->form(intval($id))));
    }

    public function create()
    {
        $data = $this->checkParams();
        $data['mer_id'] = $this->request->merId();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function update($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $this->checkParams());
        return app('json')->success('修改成功');
    }

    public function checkParams()
    {
        $data = $this->request->params(['username', 'nickname', 'mark']);
        if (!$data['username'] || !$data['nickname']) {
            throw new ValidateException('微信号或昵称不可为空');
        }
        return $data;
    }

    public function delete($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->delete((int)$id);
        return app('json')->success('删除成功');
    }
}
