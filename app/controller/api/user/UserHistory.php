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

namespace app\controller\api\user;


use crmeb\basic\BaseController;
use app\common\repositories\user\UserHistoryRepository as repository;
use think\App;

class UserHistory extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * UserHistory constructor.
     * @param App $app
     * @param repository $repository
     */
    public function __construct(App $app, repository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $type = $this->request->param('type',1);
        $uid = $this->request->uid();
        $data = $this->repository->getApiList($page,$limit,$uid,$type);
        return app('json')->success($data);
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function deleteHistory($id)
    {
        if(!$this->repository->getSearch(['uid' => $this->request->uid(),'history_id' => $id]))
            return app('json')->fail('信息不存在');
        $this->repository->delete($id);
        return app('json')->success('浏览记录已删除');
    }

    /**
     * @return mixed
     * @author Qinii
     */
    public function deleteHistoryBatch()
    {
        $params = $this->request->param('history_id');
        if(!$params) return app('json')->fail('参数不能为空');
        $this->repository->deleteBatch($this->request->uid(),$params);
        return app('json')->success('浏览记录已删除');
    }
}
