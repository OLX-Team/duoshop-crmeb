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


use app\common\repositories\store\coupon\StoreCouponProductRepository;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\coupon\StoreCouponUserRepository;
use crmeb\basic\BaseController;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class StoreCoupon
 * @package app\controller\api\store\product
 * @author xaboy
 * @day 2020/6/1
 */
class StoreCoupon extends BaseController
{
    /**
     * @var
     */
    protected $uid;

    /**
     * StoreCoupon constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        if ($this->request->isLogin()) $this->uid = $this->request->uid();
    }

    /**
     * @param StoreCouponUserRepository $repository
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/3
     */
    public function lst(StoreCouponUserRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $where = $this->request->params(['statusTag']);
        $where['uid'] = $this->uid;
        return app('json')->success($repository->userList($where, $page, $limit));
    }

    /**
     * @param StoreCouponRepository $repository
     * @param StoreCouponProductRepository $couponProductRepository
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/1
     */
    public function coupon(StoreCouponRepository $repository, StoreCouponProductRepository $couponProductRepository)
    {
        $ids = array_filter(explode(',', $this->request->param('ids')));
        if (!count($ids))
            return app('json')->success([]);
        $productCouponIds = $couponProductRepository->productByCouponId($ids);
        $productCoupon = count($productCouponIds) ? $repository->validProductCoupon($productCouponIds, $this->uid)->toArray() : [];
        return app('json')->success($productCoupon);
    }

    /**
     * @param $id
     * @param StoreCouponRepository $repository
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/1
     */
    public function merCoupon($id, StoreCouponRepository $repository)
    {
        $all = (int)$this->request->param('all');
        $coupon = $repository->validMerCoupon($id, $this->uid, $all === 1 ? null : 0)->toArray();
        return app('json')->success($coupon);
    }

    /**
     * @param $id
     * @param StoreCouponRepository $repository
     * @return mixed
     * @author xaboy
     * @day 2020/6/1
     */
    public function receiveCoupon($id, StoreCouponRepository $repository)
    {
        if (!$repository->exists($id))
            return app('json')->fail('优惠券不存在');
        $repository->receiveCoupon($id, $this->uid);
        return app('json')->success('领取成功');
    }

    /**
     * TODO 可领取的优惠券列表
     * @author Qinii
     * @day 3/14/22
     */
    public function getList(StoreCouponRepository $couponRepository)
    {
        $where = $this->request->params(['type','mer_id', 'product','is_pc',['send_type',0]]);
        [$page, $limit] = $this->getPage();
        $data = $couponRepository->apiList($where, $page, $limit, $this->uid);
        return app('json')->success($data);
    }

    public function newPeople(StoreCouponRepository $couponRepository)
    {
        $coupons = $couponRepository->newPeopleCoupon();

        foreach ($coupons as $coupon){
            if($coupon['coupon_type']){
                $coupon['use_end_time'] = explode(' ', $coupon['use_end_time'])[0] ?? '';
                $coupon['use_start_time'] = explode(' ', $coupon['use_start_time'])[0] ?? '';
            }else{
                $coupon['use_start_time'] = date('Y-m-d');
                $coupon['use_end_time'] = date('Y-m-d', strtotime('+ ' . $coupon['coupon_time'] . ' day'));
            }
            if($coupon['use_end_time']){
                $coupon['use_end_time'] = date('Y.m.d',strtotime($coupon['use_end_time']));
            }
            if($coupon['use_start_time']){
                $coupon['use_start_time'] = date('Y.m.d',strtotime($coupon['use_start_time']));
            }
        }
        return app('json')->success($coupons);
    }

}
