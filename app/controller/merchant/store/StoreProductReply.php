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


namespace app\controller\merchant\store;


use app\common\repositories\store\product\ProductReplyRepository;
use crmeb\basic\BaseController;
use think\App;

class StoreProductReply extends BaseController
{
    protected $repository;

    public function __construct(App $app, ProductReplyRepository $replyRepository)
    {
        parent::__construct($app);
        $this->repository = $replyRepository;
    }

    public function changeSort($id)
    {
        $merId = $this->request->merId();
        if (!$this->repository->merExists($merId, $id))
            return app('json')->fail('数据不存在');

        $sort = (int)$this->request->param('sort');

        $this->repository->update($id, compact('sort'));
        return app('json')->success('修改成功');
    }

}
