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


namespace app\controller\api\store\product;

use app\common\repositories\store\order\StoreOrderProductRepository;
use app\validate\api\ProductReplyValidate;
use think\App;
use crmeb\basic\BaseController;
use app\common\repositories\store\product\ProductReplyRepository as repository;

class StoreReply extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;
    protected $userInfo;

    /**
     * StoreProduct constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userInfo = $this->request->isLogin() ? $this->request->userInfo() : null;
    }

    /**
     * @Author:Qinii
     * @Date: 2020/5/28
     * @return mixed
     */
    public function lst($id)
    {
        [$page, $limit] = $this->getPage();

        $where['type'] = $this->request->param('type');
        $where['product_id'] = $id;
        return app('json')->success($this->repository->getApiList($where,$page, $limit));
    }

    public function product($id, StoreOrderProductRepository $orderProductRepository)
    {
        $orderProduct = $orderProductRepository->userOrderProduct((int)$id, $this->request->uid());
        if (!$orderProduct || !$orderProduct->orderInfo)
            return app('json')->fail('订单不存在');
        if ($orderProduct->is_reply)
            return app('json')->fail('该商品已评价');
        return app('json')->success($orderProduct->toArray());
    }

    public function reply($id, ProductReplyValidate $validate)
    {
        $data = $this->request->params(['comment', 'product_score', 'service_score', 'postage_score', ['pics', []]]);
        $validate->check($data);
        $user = $this->request->userInfo();
        $data['uid'] = $this->request->uid();
        $data['order_product_id'] = (int)$id;
        $data['nickname'] = $user['nickname'];
        $data['avatar'] = $user['avatar'];
        $this->repository->reply($data);
        return app('json')->success('评价成功');
    }

}
