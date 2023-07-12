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


use app\common\repositories\store\broadcast\BroadcastGoodsRepository;
use app\validate\merchant\BroadcastGoodsValidate;
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
        $where = $this->request->params(['status_tag', 'keyword', 'mer_valid','broadcast_goods_id']);
        return app('json')->success($this->repository->getList($this->request->merId(), $where, $page, $limit));
    }

    public function detail($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        return app('json')->success($this->repository->get($id)->append(['product'])->toArray());
    }

    public function createForm()
    {
        return app('json')->success(formToData($this->repository->createForm()));
    }

    protected function checkParams(BroadcastGoodsValidate $validate)
    {
        $data = $this->request->params(['name', 'cover_img', 'product_id', 'price']);
        $validate->check($data);
        $data['product_id'] = $data['product_id']['id'];
        return $data;
    }

    public function create(BroadcastGoodsValidate $validate)
    {
        $this->repository->create($this->request->merId(), $this->checkParams($validate));
        return app('json')->success('创建成功');
    }

    public function batchCreate(BroadcastGoodsValidate $validate)
    {
        $goods = $this->request->param('goods', []);
        if (!count($goods)) return app('json')->fail('请选中商品');
        $validate->isBatch();
        foreach ($goods as $item) {
            $validate->check((array)$item);
        }
        $this->repository->batchCreate($this->request->merId(), $goods);
        return app('json')->success('创建成功');
    }

    public function updateForm($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->updateForm($id)));
    }

    public function update($id, BroadcastGoodsValidate $validate)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $this->checkParams($validate));
        return app('json')->success('编辑成功');
    }

    public function mark($id)
    {
        $mark = (string)$this->request->param('mark');
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->mark($id, $mark);
        return app('json')->success('修改成功');
    }

    public function changeStatus($id)
    {
        $isShow = $this->request->param('is_show') == 1 ? 1 : 0;
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->isShow($id, $isShow);
        return app('json')->success('修改成功');
    }

    public function delete($id)
    {
        if (!$this->repository->merExists($id, $this->request->merId()))
            return app('json')->fail('数据不存在');
        $this->repository->delete((int)$id);
        return app('json')->success('删除成功');
    }

}
