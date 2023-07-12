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


use app\common\dao\BaseDao;
use app\common\dao\user\UserBillDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductRepository;
use crmeb\jobs\SendSmsJob;
use think\facade\Queue;
use think\Model;

/**
 * Class UserBillRepository
 * @package app\common\repositories\user
 * @author xaboy
 * @day 2020-05-07
 * @mixin UserBillDao
 */
class UserBillRepository extends BaseRepository
{
    /**
     * @var UserBillDao
     */
    protected $dao;

    const TYPE_INFO = [
        'brokerage/now_money' => '佣金转入余额',
        'brokerage/order_one' => '获得一级推广佣金',
        'brokerage/order_two' => '获得二级推广佣金',
        'brokerage/refund_one' => '退还一级佣金',
        'brokerage/refund_two' => '退还二级佣金',
        'integral/cancel' => '退回积分',
        'integral/deduction' => '购买商品',
        'integral/points' => '兑换商品',
        'integral/lock' => '下单赠送积分',
        'integral/refund' => '订单退款',
        'integral/refund_lock' => '扣除赠送积分',
        'integral/sign_integral' => '签到赠送积分',
        'integral/spread' => '邀请好友',
        'integral/sys_dec' => '系统减少积分',
        'integral/sys_inc' => '系统增加积分',
        'integral/timeout' => '积分过期',
        'mer_integral/deduction' => '积分抵扣',
        'mer_integral/refund' => '订单退款',
        'mer_lock_money/order' => '商户佣金冻结',
        'now_money/brokerage' => '佣金转入余额',
        'now_money/pay_product' => '购买商品',
        'now_money/presell' => '支付预售尾款',
        'now_money/recharge' => '余额充值',
        'now_money/sys_dec_money' => '系统减少余额',
        'now_money/sys_inc_money' => '系统增加余额',
        'mer_margin/local_margin' => '线下缴纳保证金',
        'mer_margin/pay_margin' => '线上缴纳保证金',
    ];

    /**
     * UserBillRepository constructor.
     * @param UserBillDao $dao
     */
    public function __construct(UserBillDao $dao)
    {
        $this->dao = $dao;
    }

    public function userList($where, $uid, $page, $limit)
    {
        $where['uid'] = $uid;
        $query = $this->dao->search($where)->order('create_time DESC');
        $count = $query->count();
        $list = $query->setOption('field', [])->field('bill_id,pm,title,number,balance,mark,create_time,status')->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function month(array $where)
    {
        $group = $this->dao->search($where)->field('FROM_UNIXTIME(unix_timestamp(create_time),"%Y-%m") as time')
            ->order('time DESC')->group('time')->select();
        $ret = [];
        foreach ($group as $k => $item){
            $ret[$k]['month'] = $item['time'];
            $query = $this->dao->getSearch($where)->field('title,number,create_time')->whereMonth('create_time',$item['time']);
            $ret[$k]['list'] = $query->order('create_time DESC')->select();
        }
        return $ret;
    }

    public function getList($where, $page, $limit)
    {
        $query = $this->dao->searchJoin($where)->order('a.create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function getLst($where, $page, $limit)
    {
        $query = $this->dao->search($where)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * @param int $uid
     * @param string $category
     * @param string $type
     * @param int $pm
     * @param array $data
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-05-07
     */
    public function bill(int $uid, string $category, string $type, int $pm, array $data)
    {
        $data['category'] = $category;
        $data['type'] = $type;
        $data['uid'] = $uid;
        $data['pm'] = $pm;
        $bill = $this->dao->create($data);
        if($category == 'now_money'){
            Queue::push(SendSmsJob::class,['tempId' => 'USER_BALANCE_CHANGE','id' => $bill->bill_id]);
        }
        return $bill;
    }

    /**
     * @param int $uid
     * @param string $category
     * @param string $type
     * @param array $data
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-05-07
     */
    public function incBill(int $uid, string $category, string $type, array $data)
    {
        return $this->bill($uid, $category, $type, 1, $data);
    }

    /**
     * @param int $uid
     * @param string $category
     * @param string $type
     * @param array $data
     * @return BaseDao|Model
     * @author xaboy
     * @day 2020-05-07
     */
    public function decBill(int $uid, string $category, string $type, array $data)
    {
        return $this->bill($uid, $category, $type, 0, $data);
    }

    public function type()
    {
        $data = [];
        foreach (self::TYPE_INFO as $type => $title) {
            $data[] = compact('type', 'title');
        }
        return $data;
    }

    /**
     * TODO 积分日志头部统计
     * @return array
     * @author Qinii
     * @day 6/9/21
     */
    public function getStat($merId = 0)
    {
        if($merId){
            $isusd = app()->make(ProductRepository::class)->getSearch(['mer_id' => $merId])->sum('integral_total');
            $refund = $this->dao->search(['category' => 'mer_integral','type' => 'refund','mer_id' => $merId])->sum('number');
            $numb = app()->make(ProductRepository::class)->getSearch(['mer_id' => $merId])->sum('integral_price_total');
            return [
                [
                    'className' => 'el-icon-s-cooperation',
                    'count' => $isusd,
                    'field' => '个',
                    'name' => '已使用积分（分）'
                ],
                [
                    'className' => 'el-icon-edit',
                    'count' => $refund,
                    'field' => '次',
                    'name' => '退款订单返回积分（分）'
                ],
                [
                    'className' => 'el-icon-edit',
                    'count' => $numb,
                    'field' => '次',
                    'name' => '积分抵扣金额（元）'
                ],
            ];
        }
        // 总积分
        $integral = app()->make(UserRepository::class)->search(['status' => 1])->sum('integral');
        // 客户签到次数
        $sign = app()->make(UserSignRepository::class)->getSearch([])->count('*');
        // 签到送出积分
        $sign_integral = $this->dao->search(['type' => 'sign_integral'])->sum('number');
        // 使用积分
        $isusd  = $this->dao->search(['category' => 'integral','type' => 'deduction'])->sum('number');
        $refund = $this->dao->search(['category' => 'mer_integral','type' => 'refund'])->sum('number');
        $order = $isusd - $refund;

        // 下单赠送积分
        $order_integral1 = $this->dao->search(['category' => 'integral','type' => 'lock'])->sum('number');
        $order_integral2 = $this->dao->search(['category' => 'integral','type' => 'refund_lock'])->sum('number');
        $order_integral = $order_integral1 - $order_integral2;
        $order_integral = $order_integral < 0 ? 0 : $order_integral;
        // 冻结积分
        $freeze_integral = $this->dao->lockIntegral();

        return [
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $integral,
                'field' => '个',
                'name' => '总积分'
            ],
            [
                'className' => 'el-icon-edit',
                'count' => $sign,
                'field' => '次',
                'name' => '客户签到次数'
            ],
            [
                'className' => 'el-icon-s-goods',
                'count' => $sign_integral ,
                'field' => '个',
                'name' => '签到送出积分'
            ],
            [
                'className' => 'el-icon-s-order',
                'count' => $order,
                'field' => '个',
                'name' => '使用积分'
            ],
            [
                'className' => 'el-icon-present',
                'count' => $order_integral,
                'field' => '个',
                'name' => '下单赠送积分'
            ],
            [
                'className' => 'el-icon-warning',
                'count' => $freeze_integral,
                'field' => '',
                'name' => '冻结积分'
            ],
        ];
    }


}
