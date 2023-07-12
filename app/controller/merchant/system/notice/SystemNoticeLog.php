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


namespace app\controller\merchant\system\notice;


use app\common\repositories\system\notice\SystemNoticeLogRepository;
use crmeb\basic\BaseController;
use think\App;

/**
 * Class SystemNotice
 * @package app\controller\merchant\system\notice
 * @author xaboy
 * @day 2020/11/6
 */
class SystemNoticeLog extends BaseController
{
    /**
     * @var SystemNoticeLogRepository
     */
    protected $repository;

    /**
     * SystemNoticeLog constructor.
     * @param App $app
     * @param SystemNoticeLogRepository $repository
     */
    public function __construct(App $app, SystemNoticeLogRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return \think\response\Json
     * @author xaboy
     * @day 2020/11/6
     */
    public function lst()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['is_read', 'date', 'keyword']);
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * @param $id
     * @author xaboy
     * @day 2020/11/6
     */
    public function read($id)
    {
        $this->repository->read(intval($id), $this->request->merId());
        return app('json')->success();
    }

    public function del($id)
    {
        $this->repository->del(intval($id), $this->request->merId());
        return app('json')->success();
    }

    public function unreadCount()
    {
        return app('json')->success(['count' => $this->repository->unreadCount($this->request->merId())]);
    }

}
