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
use app\common\repositories\user\UserSignRepository;
use think\App;

class UserSign extends BaseController
{
    /**
     * @var repository
     */
    protected $repository;

    /**
     * UserSign constructor.
     * @param App $app
     * @param UserSignRepository $repository
     */
    public function __construct(App $app, UserSignRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function lst()
    {
        [$page,$limit] = $this->getPage();
        $where = ['uid' => $this->request->uid()];
        $data = $this->repository->getList($where,$page,$limit);
        return app('json')->success($data);
    }

    public function create()
    {
        $uid = $this->request->uid();
        $day = date('Y-m-d',time());
        if($this->repository->getSign($uid,$day))
            return app('json')->fail('您今日已签到');
        $data = $this->repository->create($uid);
        return app('json')->success($data);
    }

    public function info()
    {
        $uid = $this->request->uid();
        $data = $this->repository->info($uid);
        return app('json')->success($data);
    }

    public function month()
    {
        $where = ['uid' => $this->request->uid()];
        return app('json')->success($this->repository->month($where));
    }
}
