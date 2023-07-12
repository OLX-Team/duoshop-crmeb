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

namespace app\controller\api\article;

use app\common\repositories\user\UserVisitRepository;
use crmeb\services\SwooleTaskService;
use think\App;
use app\common\repositories\article\ArticleRepository as repository;
use crmeb\basic\BaseController;

class Article extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * StoreBrand constructor.
     * @param App $app
     * @param repository $repository
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
    public function lst($cid)
    {
        [$page, $limit] = $this->getPage();
        $where = ['status' => 1,'cid' => $cid];
        return app('json')->success($this->repository->search(0,$where, $page, $limit));
    }

    public function detail($id)
    {
        if (!$this->repository->merApiExists($id))
            return app('json')->fail('文章不存在');
        $data = $this->repository->get($id,0);
        if ($this->request->isLogin()) {
            $uid = $this->request->uid();
            $make = app()->make(UserVisitRepository::class);
            $count = $make->search(['uid' => $uid, 'type' => 'article'])->where('type_id', $id)->whereTime('create_time', '>', date('Y-m-d H:i:s', strtotime('- 300 seconds')))->count();
            if (!$count) {
                SwooleTaskService::visit(intval($uid), $id, 'article');
                $this->repository->incVisit($id);
            }
        }

        return app('json')->success($data);
    }

    public function list()
    {
        $where = ['status' => 1];
        return app('json')->success($this->repository->search(0,$where, 1, 9));
    }
}
