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


use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductReplyRepository;
use app\common\repositories\store\product\ProductRepository;
use app\validate\admin\StoreProductReplyValidate;
use crmeb\services\ApiResponseService;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class StoreProductReply
 * @package app\controller\admin\store
 * @author xaboy
 * @day 2020/6/1
 */
class StoreProductReply extends BaseController
{
    /**
     * @var ProductReplyRepository
     */
    protected $repository;

    /**
     * StoreProductReply constructor.
     * @param App $app
     * @param ProductReplyRepository $repository
     */
    public function __construct(App $app, ProductReplyRepository $repository)
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
     * @day 2020/6/1
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'nickname', 'is_reply', 'date', 'product_id']);
        $where['mer_id'] = $this->request->merId() ?: '';
        return \app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * @param null $productId
     * @throws FormBuilderException
     * @author xaboy
     * @day 2020/6/1
     */
    public function virtualForm($productId = null)
    {
        if ($productId && !app()->make(ProductRepository::class)->exists($productId)) {
            app('json')->fail('商品不存在');
        }
        return app('json')->success(formToData($this->repository->form($productId)));
    }

    /**
     * @param StoreProductReplyValidate $validate
     * @return mixed
     * @author xaboy
     * @day 2020/6/1
     */
    public function virtualReply(StoreProductReplyValidate $validate)
    {
        $data = $this->checkParams($validate);
        $_name = mb_substr($data['nickname'], 0, 1) . '***';
        $name = (strLen($data['nickname'])  > 6) ? $_name . mb_substr($data['nickname'], -1, 1) : $_name;
        $data['nickname'] = $name;
        $productId = $data['product_id'];
        unset($data['product_id']);
        $this->repository->createVirtual([$productId], $data);

        return app('json')->success('添加成功');
    }

    public function replyForm($id)
    {
        $merId = $this->request->merId();
        if ($merId)
            if (!$this->repository->merExists($merId, $id))
                return app('json')->fail('数据不存在');;
        return app('json')->success(formToData($this->repository->replyForm($id, $merId)));
    }

    public function reply($id)
    {
        $merId = $this->request->merId();
        if ($merId)
            if (!$this->repository->merExists($merId, $id))
                return app('json')->fail('数据不存在');
        $merchant_reply_content = $this->request->param('content');
        if (!$merchant_reply_content)
            return app('json')->fail('请输入回复的内容');
        $merchant_reply_time = date('Y-m-d H:i:s');
        $is_reply = 1;
        $this->repository->update($id, compact('is_reply', 'merchant_reply_content', 'merchant_reply_time'));
        return app('json')->success('回复成功');
    }

    /**
     * @param $id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020/6/1
     */
    public function delete($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->delete($id);
        return app('json')->success('删除成功');
    }

    /**
     * @param StoreProductReplyValidate $validate
     * @return array
     * @author xaboy
     * @day 2020/6/1
     */
    public function checkParams(StoreProductReplyValidate $validate)
    {
        $data = $this->request->params([['product_id', []], 'nickname', 'comment', 'product_score', 'service_score', 'postage_score', 'avatar', ['pics', '']]);
        $validate->check($data);
        $data['product_id'] = $data['product_id']['id'] ?? 0;
        return $data;
    }

    public function sort($id)
    {
        $data = $this->request->params(['sort']);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $this->repository->update($id, $data);
        return app('json')->success('修改成功');
    }
}
