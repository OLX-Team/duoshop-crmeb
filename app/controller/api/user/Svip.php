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

use app\common\model\user\UserOrder;
use app\common\repositories\store\coupon\StoreCouponRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\store\product\SpuRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\groupData\GroupRepository;
use app\common\repositories\system\serve\ServeOrderRepository;
use app\common\repositories\user\MemberinterestsRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserOrderRepository;
use app\common\repositories\user\UserRepository;
use crmeb\basic\BaseController;
use app\common\repositories\user\FeedbackRepository;
use think\App;
use think\exception\ValidateException;

class Svip extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserBillRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        if (!systemConfig('svip_switch_status'))  throw new ValidateException('付费会员未开启');
    }

    /**
     * TODO 会员卡类型列表
     * @param GroupRepository $groupRepository
     * @param GroupDataRepository $groupDataRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/7
     */
    public function getTypeLst(GroupRepository $groupRepository,GroupDataRepository  $groupDataRepository)
    {
        $group_id = $groupRepository->getSearch(['group_key' => 'svip_pay'])->value('group_id');
        $where['group_id'] = $group_id;
        $where['status'] = 1;
        $list = $groupDataRepository->getSearch($where)->field('group_data_id,value,sort,status')->order('sort DESC')->select();
        if ($this->request->isLogin() && $this->request->userInfo()->is_svip != -1) {
            foreach ($list as $item) {
                if ($item['value']['svip_type'] != 1) $res[] = $item;
            }
        }
        $list = $res ?? $list;
        $def = [];
        if ($list && isset($list[0])) {
            $def = $list[0] ? (['group_data_id' => $list[0]['group_data_id']] + $list[0]['value']) : [];
        }
        return app('json')->success(['def' => $def, 'list' => $list]);
    }

    /**
     * TODO 购买会员
     * @param $id
     * @param GroupDataRepository $groupDataRepository
     * @param ServeOrderRepository $serveOrderRepository
     * @return \think\response\Json|void
     * @author Qinii
     * @day 2022/11/7
     */
    public function createOrder($id, GroupDataRepository $groupDataRepository, UserOrderRepository $userOrderRepository)
    {
        $params = $this->request->params(['pay_type','return_url']);
        if (!in_array($params['pay_type'], ['weixin', 'routine', 'h5', 'alipay', 'alipayQr', 'weixinQr'], true))
            return app('json')->fail('请选择正确的支付方式');
        $res = $groupDataRepository->getWhere(['group_data_id' => $id, 'status' => 1]);
        if (!$res) return  app('json')->fail('参数有误～');
        if ($this->request->userInfo()->is_svip == 3)
            return  app('json')->fail('您已经是终身会员～');
        if ($this->request->userInfo()->is_svip !== -1 && $res['value']['svip_type'] == 1)
            return  app('json')->fail('请选择其他会员类型');
        $params['is_app'] = $this->request->isApp();
        return $userOrderRepository->add($res,$this->request->userInfo(),$params);
    }

    /**
     * TODO 会员中心个人信息
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/9
     */
    public function svipUserInfo()
    {
        if ($this->request->isLogin()) {
            $user = app()->make(UserRepository::class)->getSearch([])->field('uid,nickname,avatar,is_svip,svip_endtime,svip_save_money')->find($this->request->uid());
            if ($user && $user['is_svip'] == 3) $user['svip_endtime'] = date('Y-m-d H:i:s',strtotime("+100 year"));
        }
        $data['user'] = $user ?? new \stdClass();
        $data['interests'] = systemConfig('svip_switch_status') ? app()->make(MemberinterestsRepository::class)->getInterestsByLevel(MemberinterestsRepository::TYPE_SVIP) : [];

        return app('json')->success($data);
    }

    /**
     * TODO 获取会员优惠券列表
     * @param StoreCouponRepository $couponRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/17
     */
    public function svipCoupon(StoreCouponRepository $couponRepository)
    {
        $where['send_type'] = $couponRepository::GET_COUPON_TYPE_SVIP;
        $uid = $this->request->isLogin() ? $this->request->uid() : null;
        $data = $couponRepository->sviplist($where, $uid);
        return app('json')->success($data);
    }

    /**
     * TODO 领取会员优惠券
     * @param $id
     * @param StoreCouponRepository $couponRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/17
     */
    public function receiveCoupon($id, StoreCouponRepository $couponRepository)
    {
        if (!$this->request->userInfo()->is_svip)
            return app('json')->fail('您还不是付费会员');
        if (!$couponRepository->exists($id))
            return app('json')->fail('优惠券不存在');
        try {
            $couponRepository->receiveSvipCounpon($id, $this->request->uid());
        } catch (\Exception $e) {
            return app('json')->fail('优惠券已被领完');
        }
        return app('json')->success('领取成功');
    }

    /**
     * TODO 会员专属商品
     * @param SpuRepository $spuRepository
     * @return \think\response\Json
     * @author Qinii
     * @day 2022/11/17
     */
    public function svipProductList(SpuRepository $spuRepository)
    {
        [$page, $limit] = $this->getPage();
        $user = $this->request->isLogin() ? $this->request->userInfo() : null;
        $where['is_gift_bag'] = 0;
        $where['product_type'] = 0;
        $where['order'] = 'star';
        $where['svip'] = 1;
        $data = $spuRepository->getApiSearch($where, $page, $limit, $user);
        return  app('json')->success($data);
    }
}
