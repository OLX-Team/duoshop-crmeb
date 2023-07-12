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


namespace app\controller\admin\store;


use app\common\repositories\store\broadcast\BroadcastGoodsRepository;
use crmeb\basic\BaseController;
use think\App;

class BroadcastGoods extends BaseController
{
    protected $repository;

    public function __construct(App $app, BroadcastGoodsRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'status_tag', 'is_trader', 'mer_valid','broadcast_goods_id']);
        return app('json')->success($this->repository->adminList($where, $page, $limit));
    }

    public function detail($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id)->append(['product'])->toArray());
    }

    public function applyForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');

        return app('json')->success(formToData($this->repository->applyForm($id)));
    }

    public function apply($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        [$status, $msg] = $this->request->params(['status', 'msg'], true);
        $status = $status == 1 ? 1 : -1;
        if ($status == -1 && !$msg)
            return app('json')->fail('请输入理由');
        $this->repository->apply($id, $status, $msg);
        return app('json')->success('操作成功');
    }

    public function changeStatus($id)
    {
        $isShow = $this->request->param('is_show') == 1 ? 1 : 0;
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->isShow($id, $isShow, true);
        return app('json')->success('修改成功');
    }

    public function sort($id)
    {
        $sort = (int)$this->request->param('sort');
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->change($id, compact('sort'));
        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

}
