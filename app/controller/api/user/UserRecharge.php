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
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\user\UserRechargeRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\services\WechatService;
use think\App;

class UserRecharge extends BaseController
{
    protected $repository;

    public function __construct(App $app, UserRechargeRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function brokerage(UserRepository $userRepository)
    {
        $brokerage = (float)$this->request->param('brokerage');
        if ($brokerage <= 0)
            return app('json')->fail('请输入正确的充值金额!');
        $user = $this->request->userInfo();
        if ($user->brokerage_price < $brokerage)
            return app('json')->fail('剩余可用佣金不足' . $brokerage);
        $config = systemConfig(['recharge_switch', 'balance_func_status']);
        if (!$config['recharge_switch'] || !$config['balance_func_status'])
            return app('json')->fail('余额充值功能已关闭');
        $userRepository->switchBrokerage($user, $brokerage);
        return app('json')->success('转换成功');
    }

    public function recharge(GroupDataRepository $groupDataRepository)
    {
        [$type, $price, $rechargeId, $return_url] = $this->request->params(['type', 'price', 'recharge_id', 'return_url'], true);
        if (!in_array($type, ['weixin', 'routine', 'h5', 'alipay', 'alipayQr', 'weixinQr']))
            return app('json')->fail('请选择正确的支付方式!');
        if($price > 1000000){
            return app('json')->fail('充值金额超出最大限制');
        }
        $app = $this->request->isApp();
        $user = $this->request->userInfo();
        $wechatUserId = $user['wechat_user_id'];
        if (!$wechatUserId && in_array($type, [$this->repository::TYPE_ROUTINE, $this->repository::TYPE_WECHAT]) && !$app)
            return app('json')->fail('请关联微信' . ($type == 'weixin' ? '公众号' : '小程序') . '!');
        $config = systemConfig(['store_user_min_recharge', 'recharge_switch', 'balance_func_status']);
        if (!$config['recharge_switch'] || !$config['balance_func_status'])
            return app('json')->fail('余额充值功能已关闭');
        if ($rechargeId) {
            if (!intval($rechargeId))
                return app('json')->fail('请选择充值金额!');
            $rule = $groupDataRepository->merGet(intval($rechargeId), 0);
            if (!$rule || !isset($rule['price']) || !isset($rule['give']))
                return app('json')->fail('您选择的充值方式已下架!');
            $give = floatval($rule['give']);
            $price = floatval($rule['price']);
            if ($price <= 0)
                return app('json')->fail('请选择正确的充值金额!');
        } else {
            $price = floatval($price);
            if ($price <= 0)
                return app('json')->fail('请输入正确的充值金额!');
            if ($price < $config['store_user_min_recharge'])
                return app('json')->fail('最低充值' . floatval($config['store_user_min_recharge']));
            $give = 0;
        }
        $recharge = $this->repository->create($this->request->uid(), $price, $give, $type);
        return app('json')->success($this->repository->pay($type, $user, $recharge, $return_url, $app));
    }
}
