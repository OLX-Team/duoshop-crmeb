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


namespace app\common\repositories\user;

use app\common\dao\user\UserRechargeDao;
use app\common\model\user\User;
use app\common\model\user\UserRecharge;
use app\common\repositories\BaseRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\services\PayService;
use think\facade\Db;
use think\facade\Queue;

/**
 * Class UserRechargeRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020/6/2
 * @mixin UserRechargeDao
 */
class UserRechargeRepository extends BaseRepository
{
    const TYPE_WECHAT = 'weixin';
    const TYPE_ROUTINE = 'routine';
    /**
     * UserRechargeRepository constructor.
     * @param UserRechargeDao $dao
     */
    public function __construct(UserRechargeDao $dao)
    {
        $this->dao = $dao;
    }

    public function create($uid, float $price, float $givePrice, string $type)
    {
        return $this->dao->create([
            'uid' => $uid,
            'price' => $price,
            'give_price' => $givePrice,
            'recharge_type' => $type,
            'paid' => 0,
            'order_id' => $this->dao->createOrderId($uid)
        ]);
    }

    public function getList($where, $page, $limit)
    {
        $query = $this->dao->searchJoinQuery($where)->order('a.pay_time DESC,a.create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }


    public function priceByGive($price)
    {
        $quota = systemGroupData('user_recharge_quota');
        $give = 0;
        foreach ($quota as $item) {
            $min = floatval($item['price']);
            $_give = floatval($item['give']);
            if ($price > $min) $give = $_give;
        }
        return $give;
    }

    /**
     * @param string $type
     * @param User $user
     * @param UserRecharge $recharge
     * @param string $return_url
     * @return mixed
     * @author xaboy
     * @day 2020/10/22
     */
    public function pay(string $type, User $user, UserRecharge $recharge, $return_url = '', $isApp = false)
    {
        if (in_array($type, ['weixin', 'alipay'], true) && $isApp) {
            $type .= 'App';
        }
        event('user.recharge.before', compact('user', 'recharge', 'type', 'isApp'));
        $service = new PayService($type, $recharge->getPayParams($type === 'alipay' ? $return_url : ''),'user_recharge');
        $config = $service->pay($user);
        return $config + ['recharge_id' => $recharge['recharge_id'], 'type' => $type];
    }

    /**
     * //TODO 余额充值成功
     *
     * @param $orderId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author xaboy
     * @day 2020/6/19
     */
    public function paySuccess($orderId)
    {
        $recharge = $this->dao->getWhere(['order_id' => $orderId]);
        if ($recharge->paid == 1) return;
        $recharge->paid = 1;
        $recharge->pay_time = date('Y-m-d H:i:s');

        Db::transaction(function () use ($recharge) {
            $price = bcadd($recharge->price, $recharge->give_price, 2);
            $mark = '成功充值余额' . floatval($recharge->price) . '元' . ($recharge->give_price > 0 ? ',赠送' . $recharge->give_price . '元' : '');
            app()->make(UserBillRepository::class)->incBill($recharge->user->uid, 'now_money', 'recharge', [
                'link_id' => $recharge->recharge_id,
                'status' => 1,
                'title' => '余额充值',
                'number' => $price,
                'mark' => $mark,
                'balance' => $recharge->user->now_money
            ]);
            $recharge->user->now_money = bcadd($recharge->user->now_money, $price, 2);
            $recharge->user->save();
            $recharge->save();
        });
        Queue::push(SendSmsJob::class,['tempId' => 'USER_BALANCE_CHANGE', 'id' =>$orderId]);
        event('user.recharge',compact('recharge'));
    }
}
