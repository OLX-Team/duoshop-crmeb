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


namespace app\controller\merchant\user;


use app\common\repositories\store\coupon\StoreCouponUserRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserLabelRepository;
use app\common\repositories\user\UserMerchantRepository;
use crmeb\basic\BaseController;
use FormBuilder\Exception\FormBuilderException;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class UserMerchant
 * @package app\controller\merchant\user
 * @author xaboy
 * @day 2020/10/20
 */
class UserIntegral extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserBillRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * TODO 积分日志
     * @return \think\response\Json
     * @author Qinii
     * @day 6/9/21
     */
    public function getList()
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['keyword', 'date']);
        $where['category'] = 'mer_integral';
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }

    /**
     * TODO
     * @return \think\response\Json
     * @author Qinii
     * @day 6/9/21
     */
    public function getTitle()
    {
        return app('json')->success($this->repository->getStat($this->request->merId()));
    }

}
