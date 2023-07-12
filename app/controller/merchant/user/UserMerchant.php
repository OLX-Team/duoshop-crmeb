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
class UserMerchant extends BaseController
{
    /**
     * @var UserMerchantRepository
     */
    protected $repository;

    /**
     * UserMerchant constructor.
     * @param App $app
     * @param UserMerchantRepository $repository
     */
    public function __construct(App $app, UserMerchantRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * @return \think\response\Json
     * @author xaboy
     * @day 2020/10/20
     */
    public function getList()
    {
        $where = $this->request->params(['nickname', 'sex', 'is_promoter', 'user_time_type', 'user_time', 'pay_count', 'label_id', 'user_type']);
        [$page, $limit] = $this->getPage();
        $where['mer_id'] = $this->request->merId();
        return app('json')->success($this->repository->getList($where, $page, $limit));
    }


    /**
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws FormBuilderException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-05-08
     */
    public function changeLabelForm($id)
    {
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        return app('json')->success(formToData($this->repository->changeLabelForm($this->request->merId(), $id)));
    }


    /**
     * @param $id
     * @param UserLabelRepository $labelRepository
     * @return mixed
     * @throws DbException
     * @author xaboy
     * @day 2020-05-08
     */
    public function changeLabel($id, UserLabelRepository $labelRepository)
    {
        $label_id = (array)$this->request->param('label_id', []);
        if (!$this->repository->exists($id))
            return app('json')->fail('数据不存在');
        $merId = $this->request->merId();
        $label_id = $labelRepository->intersection((array)$label_id, $merId, 0);
        $label_id = array_unique(array_merge($label_id, $this->repository->get($id)->authLabel));
        $label_id = implode(',', $label_id);
        $this->repository->update($id, compact('label_id'));
        return app('json')->success('修改成功');
    }

    public function order($uid)
    {
        [$page, $limit] = $this->getPage();
        $data = app()->make(StoreOrderRepository::class)->userMerList($uid, $this->request->merId(), $page, $limit);
        return app('json')->success($data);
    }

    public function coupon($uid)
    {
        [$page, $limit] = $this->getPage();
        $data = app()->make(StoreCouponUserRepository::class)->userList(['mer_id' => $this->request->merId(), 'uid' => (int)$uid], $page, $limit);
        return app('json')->success($data);
    }

}
