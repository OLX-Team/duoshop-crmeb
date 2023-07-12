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

namespace app\controller\merchant\store\service;

use app\common\repositories\store\service\StoreServiceReplyRepository;
use app\validate\merchant\ServiceReplyValidate;
use crmeb\basic\BaseController;
use think\App;

class StoreServiceReply extends BaseController
{
    /**
     * @var StoreServiceReplyRepository
     */
    protected $repository;

    /**
     * StoreService constructor.
     * @param App $app
     * @param StoreServiceReplyRepository $repository
     */
    public function __construct(App $app, StoreServiceReplyRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();

        $where = $this->request->params(['keyword', 'status']);
        $where['mer_id'] = $this->request->merId();

        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    public function create()
    {
        $data = $this->checkParams();
        $this->repository->create($data);
        return app('json')->success('添加成功');
    }

    public function update($id)
    {
        $data = $this->checkParams();
        if (!$this->repository->existsWhere(['mer_id' => $data['mer_id'], 'service_reply_id' => $id])) {
            return app('json')->fail('数据不存在');
        }
        $this->repository->update($id, $data);
        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        if (!$this->repository->existsWhere(['mer_id' => $this->request->merId(), 'service_reply_id' => $id])) {
            return app('json')->fail('数据不存在');
        }
        $this->repository->delete((int)$id);
        return app('json')->success('删除成功');
    }

    public function changeStatus($id)
    {
        $data = $this->request->params(['status']);
        if (!$this->repository->existsWhere(['mer_id' => $this->request->merId(), 'service_reply_id' => $id])) {
            return app('json')->fail('数据不存在');
        }
        $this->repository->update($id, $data);
        return app('json')->success('修改成功');
    }

    public function checkParams()
    {
        $data = $this->request->params(['keyword', 'status', 'content', 'type']);
        app()->make(ServiceReplyValidate::class)->check($data);
        $data['mer_id'] = $this->request->merId();
        return $data;
    }

}
