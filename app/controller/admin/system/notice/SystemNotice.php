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


namespace app\controller\admin\system\notice;


use app\common\repositories\system\notice\SystemNoticeRepository;
use app\validate\admin\SystemNoticeValidate;
use crmeb\basic\BaseController;
use think\App;

/**
 * Class SystemNotice
 * @package app\controller\merchant\system\notice
 * @author xaboy
 * @day 2020/11/6
 */
class SystemNotice extends BaseController
{
    /**
     * @var SystemNoticeRepository
     */
    protected $repository;

    /**
     * SystemNotice constructor.
     * @param App $app
     * @param SystemNoticeRepository $repository
     */
    public function __construct(App $app, SystemNoticeRepository $repository)
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
        $where = $this->request->params(['keyword', 'date']);
        [$page, $limit] = $this->getPage();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * @param SystemNoticeValidate $validate
     * @return \think\response\Json
     * @author xaboy
     * @day 2020/11/6
     */
    public function create(SystemNoticeValidate $validate)
    {
        $data = $this->request->params(['type', 'mer_id', 'is_trader', 'category_id', 'notice_title', 'notice_content']);
        $validate->check($data);
        $this->repository->create($data, $this->request->adminId());
        return app('json')->success('发布成功');
    }

}
