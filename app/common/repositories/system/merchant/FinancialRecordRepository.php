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


namespace app\common\repositories\system\merchant;


use app\common\dao\system\merchant\FinancialRecordDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\order\StoreOrderRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserRechargeRepository;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class FinancialRecordRepository
 * @package app\common\repositories\system\merchant
 * @author xaboy
 * @day 2020/8/5
 * @mixin FinancialRecordDao
 */
class FinancialRecordRepository extends BaseRepository
{
    public function __construct(FinancialRecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 5/7/21
     */
    public function getList(array $where,int $page,int $limit)
    {
        $query = $this->dao->search($where)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * TODO 流水头部计算
     * @param int|null $merId
     * @param array $where
     * @return array
     * @author Qinii
     * @day 5/7/21
     */
    public function  getFiniancialTitle(?int $merId,array $where)
    {
        /**
         * 平台支出
         * 商户的收入 order_true + 佣金 brokerage_one,brokerage_two + 手续费 refund_charge + 商户预售收入 presell_true
         *
         * 商户支出
         * 退回收入 refund_order + （佣金 brokerage_one,brokerage_two  -  退回佣金 refund_brokerage_two,refund_brokerage_one ） + （手续费  order_charge + 预售手续费 presell_charge  - 平台退给商户的手续费 refund_charge ）
         */
        $where['is_mer'] = $merId;
        if($merId){
            //商户收入
            $income = $this->dao->search($where)->where('financial_type','in',['order','mer_presell'])->sum('number');
            //商户支出
            $expend_ = $this->dao->search($where)->where('financial_type','in',['refund_order','brokerage_one','brokerage_two','order_charge','presell_charge'])->sum('number');
            $_expend = $this->dao->search($where)->where('financial_type','in',['refund_charge','refund_brokerage_two','refund_brokerage_one'])->sum('number');
            $expend = bcsub($expend_,$_expend,2);
            $msg = '商户';
        }else{
            //平台收入
            $income = $this->dao->search($where)->where('financial_type','in',['order','order_presell','presell'])->sum('number');
            //平台支出
            $expend = $this->dao->search($where)->where('financial_type','in',['brokerage_one','brokerage_two','order_true','refund_charge','presell_true','order_platform_coupon','order_svip_coupon'])->sum('number');
            $msg = '平台';
        }
        $data = [
            [
                'className' => 'el-icon-s-goods',
                'count' => $income,
                'field' => '元',
                'name' => $msg.'收入'
            ],
            [
                'className' => 'el-icon-s-order',
                'count' => $expend,
                'field' => '元',
                'name' => $msg.'支出'
            ],
        ];
        return $data;
    }

    /**
     * TODO 平台头部统计
     * @param $where
     * @return array
     * @author Qinii
     * @day 3/23/21
     */
    public function getAdminTitle($where)
    {
        //订单收入总金额
        $count = $this->dao->search($where)->where('financial_type','in',['order','order_presell','presell'])->sum('number');
        //退款支出金额
        $refund_order = $this->dao->search($where)->where('financial_type','refund_order')->sum('number');
        //佣金支出金额
        $brokerage_ = $this->dao->search($where)->where('financial_type','in',['brokerage_one','brokerage_two'])->sum('number');
        $_brokerage = $this->dao->search($where)->where('financial_type','in',['refund_brokerage_two','refund_brokerage_one'])->sum('number');
        $brokerage = bcsub($brokerage_,$_brokerage,2);
        //平台手续费
        $charge_ = $this->dao->search($where)->where('financial_type','in',['order_charge','presell_charge'])->sum('number');
        $_charge = $this->dao->search($where)->where('financial_type','refund_charge')->sum('number');
        $charge = bcsub($charge_,$_charge,2);
        //优惠券费用 ,'order_platform_coupon','order_svip_coupon'
        $coupon = $this->dao->search($where)->where('financial_type','in',['order_platform_coupon','order_svip_coupon'])->sum('number');
        $_coupon = $this->dao->search($where)->where('financial_type','in',['refund_platform_coupon','refund_svip_coupon'])->sum('number');
        $coupon = bcsub($coupon,$_coupon,2);
        //充值金额
        $bill_where = [
            'status' => 1,
            'date' => $where['date'],
            'category' => 'now_money',
        ];
        $bill = app()->make(UserBillRepository::class)->search($bill_where)->where('type','in',['sys_inc_money','recharge'])->sum('number');
        //充值消费金额
        $bill_where = [
            'pm' => 0,
            'status' => 1,
            'date' => $where['date'],
            'category' => 'now_money',
        ];
        $_bill = app()->make(UserBillRepository::class)->search($bill_where)->where('type','in',['presell','pay_product','sys_dec_money'])->sum('number');
        //产生交易的商户数
        $mer_number = $this->dao->search($where)->group('mer_id')->count();

        $stat = [
            [
                'className' => 'el-icon-s-goods',
                'count' => $count,
                'field' => '元',
                'name' => '订单收入总金额'
            ],
            [
                'className' => 'el-icon-s-order',
                'count' => $refund_order,
                'field' => '元',
                'name' => '退款支出金额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $brokerage,
                'field' => '元',
                'name' => '佣金支出金额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $charge,
                'field' => '元',
                'name' => '平台手续费'
            ],
            [
                'className' => 'el-icon-s-finance',
                'count' => $bill,
                'field' => '元',
                'name' => '充值金额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $_bill,
                'field' => '元',
                'name' => '充值消费金额'
            ],
            [
                'className' => 'el-icon-s-goods',
                'count' => $mer_number,
                'field' => '个',
                'name' => '产生交易的商户数'
            ],
            [
                'className' => 'el-icon-s-goods',
                'count' => $coupon,
                'field' => '元',
                'name' => '优惠券金额'
            ]
        ];
       return compact('stat');
    }

    /**
     * TODO 商户头部统计
     * @param $where
     * @return array
     * @author Qinii
     * @day 5/6/21
     */
    public function getMerchantTitle($where)
    {
        //商户收入
        $count = $this->dao->search($where)->where('financial_type','in',['order','mer_presell'])->sum('number');
        //平台优惠券
        $coupon = $this->dao->search($where)->where('financial_type','in',['order_platform_coupon','order_svip_coupon'])->sum('number');
        //商户余额
        $mer_money = app()->make(MerchantRepository::class)->search(['mer_id' => $where['is_mer']])->value('mer_money');
        //最低提现额度
        $extract_minimum_line = systemConfig('extract_minimum_line');
        //商户可提现金额
        $_line = bcsub($mer_money,$extract_minimum_line,2);
        //退款支出金额
        $refund_order = $this->dao->search($where)->where('financial_type','refund_order')->sum('number');
        //佣金支出金额
        $_brokerage = $this->dao->search($where)->where('financial_type','in',['brokerage_one','brokerage_two'])->sum('number');
        $refund_brokerage = $this->dao->search($where)->where('financial_type','in',['refund_brokerage_one','refund_brokerage_two'])->sum('number');
        $brokerage = bcsub($_brokerage,$refund_brokerage,2);
        //平台手续费
        $refund_true = $this->dao->search($where)->where('financial_type','in',['order_charge','presell_charge'])->sum('number');
        $order_charge = $this->dao->search($where)->where('financial_type','refund_charge')->sum('number');
        $charge = bcsub($refund_true,$order_charge,2);
        //商户可提现金额
//        $bill_order = app()->make(StoreOrderRepository::class)->search(['paid' => 1,'date' => $where['date'],'pay_type' => 0])->sum('pay_price');
        $merLockMoney = app()->make(UserBillRepository::class)->merchantLickMoney($where['is_mer']);
        $stat = [
            [
                'className' => 'el-icon-s-goods',
                'count' => $count,
                'field' => '元',
                'name' => '商户收入'
            ],
            [
                'className' => 'el-icon-s-order',
                'count' => $mer_money,
                'field' => '元',
                'name' => '商户余额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => ($_line < 0) ?  0 : $_line,
                'field' => '元',
                'name' => '商户可提现金额'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $refund_order,
                'field' => '元',
                'name' => '退款支出'
            ],
            [
                'className' => 'el-icon-s-finance',
                'count' => $brokerage,
                'field' => '元',
                'name' => '佣金支出'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $charge,
                'field' => '元',
                'name' => '平台手续费'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $coupon,
                'field' => '元',
                'name' => '平台优惠券补贴'
            ],
            [
                'className' => 'el-icon-s-cooperation',
                'count' => $merLockMoney,
                'field' => '元',
                'name' => '商户冻结金额'
            ],
        ];
        return compact('stat');
    }

    /**
     * TODO 月账单
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 3/23/21
     */
    public function getAdminList(array $where,int $page,int $limit)
    {
        //日
        if($where['type'] == 1){
            $field = Db::raw('from_unixtime(unix_timestamp(create_time),\'%Y-%m-%d\') as time');
        }else{
        //月
            if(!empty($where['date'])){
                list($startTime, $endTime) = explode('-', $where['date']);
                $firstday = date('Y/m/01', strtotime($startTime));
                $lastday_ = date('Y/m/01', strtotime($endTime));
                $lastday = date('Y/m/d', strtotime("$lastday_ +1 month -1 day"));
               $where['date'] = $firstday.'-'.$lastday;
            }
            $field = Db::raw('from_unixtime(unix_timestamp(create_time),\'%Y-%m\') as time');
        }
        $make = app()->make(UserBillRepository::class);

        $query = $this->dao->search($where)->field($field)->group("time")->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->select()->each(function ($item) use($where){

                $key = $where['is_mer'] ? $where['is_mer'].'_financial_record_list_'.$item['time'] : 'sys_financial_record_list_'.$item['time'];
                if(($where['type'] == 1 && ($item['time'] == date('Y-m-d',time()))) || ($where['type'] == 2 && ($item['time'] == date('Y-m',time())))){
                    $income = ($this->countIncome($where['type'],$where,$item['time']))['number'] ;
                    $expend = ($this->countExpend($where['type'],$where,$item['time']))['number'] ;
                    $ret = [
                        'income' => $income,
                        'expend' => $expend ,
                        'charge' => bcsub($income,$expend,2),
                    ];

                }else{
                    if(!$ret = Cache::get($key)){
                        $income = ($this->countIncome($where['type'],$where,$item['time']))['number'] ;
                        $expend = ($this->countExpend($where['type'],$where,$item['time']))['number'] ;
                        $ret = [
                            'income' => $income,
                            'expend' => $expend ,
                            'charge' => bcsub($income,$expend,2),
                        ];
                        Cache::tag('system')->set($key,$ret,24 * 3600);
                    }
                }
                $item['income'] = $ret['income'];
                $item['expend'] = $ret['expend'];
                $item['charge'] = $ret['charge'];
            });

        return compact('count','list');
    }

    /**
     * TODO 平台详情
     * @param int $type
     * @param array $where
     * @return mixed
     * @author Qinii
     * @day 3/23/21
     */
    public function adminDetail(int $type,array $where)
    {
        $date_ = strtotime($where['date']);unset($where['date']);
        $date = ($type == 1) ? date('Y-m-d',$date_) : date('Y-m',$date_);
        $income = $this->countIncome($type,$where,$date);
        $bill = $this->countBill($type,$date);
        $expend = $this->countExpend($type,$where,$date);
        $charge = bcsub($income['number'],$expend['number'],2);
        $data['date']   = $date;
        $data['income'] = [
            'title' => '订单收入总金额',
            'number' => $income['number'] ,
            'count' => $income['count'].'笔',
            'data' => [
                ['订单支付',        $income['number_order'].'元',  $income['count_order'].'笔'],
                ['退回优惠券补贴', $income['number_coupon'].'元', $income['count_coupon'].'笔'],
                ['退回会员优惠券补贴', $income['number_svipcoupon'].'元', $income['count_svipcoupon'].'笔'],
            ]
        ];
        $data['bill'] =  [
            'title' => '充值金额',
            'number' => $bill['number'] ,
            'count' => $bill['count'].'笔',
            'data' => []
        ];
        $data['expend'] = [
            'title' => '支出总金额',
            'number' => $expend['number'] ,
            'count' => $expend['count'].'笔',
            'data' => [
                ['应付商户金额',   $expend['number_order'] .'元',     $expend['count_order'].'笔'],
                ['佣金',          $expend['number_brokerage'] .'元', $expend['count_brokerage'].'笔'],
                ['返还手续费',     $expend['number_charge'] .'元',    $expend['count_charge'].'笔'],
                ['优惠券补贴',$expend['number_coupon'] .'元',    $expend['count_coupon'].'笔'],
                ['会员优惠券补贴',$expend['number_svipcoupon'] .'元',    $expend['count_svipcoupon'].'笔'],
            ]
        ];
        $data['charge'] = [
            'title' => '平台手续费收入总金额',
            'number' => $charge ,
            'count' => '',
            'data' => []
        ];
        return $data;
    }

    /**
     * TODO 商户详情
     * @param int $type
     * @param array $where
     * @return mixed
     * @author Qinii
     * @day 5/6/21
     */
    public function merDetail(int $type,array $where)
    {
        $date_ = strtotime($where['date']); unset($where['date']);
        $date = ($type == 1) ? date('Y-m-d',$date_) : date('Y-m',$date_);
        $income = $this->countIncome($type,$where,$date);
        $expend = $this->countExpend($type,$where,$date);
        $charge = bcsub($income['number'],$expend['number'],2);

        $data['date']   = $date;
        $data['income'] = [
            'title' => '订单收入总金额',
            'number' => $income['number'] ,
            'count' => $income['count'].'笔',
            'data' => [
                ['订单支付',      $income['number_order'].'元',    $income['count_order'].'笔'],
                ['优惠券补贴', $income['number_coupon'].'元',   $income['count_coupon'].'笔'],
                ['会员优惠券补贴', $income['number_svipcoupon'].'元',   $income['count_svipcoupon'].'笔'],
            ]
        ];
        $data['expend'] = [
            'title' => '支出总金额',
            'number' => $expend['number'] ,
            'count' => $expend['count'].'笔',
            'data' => [
                [
                    '平台手续费',
                    bcsub($expend['number_order_charge'],$expend['number_charge'],2) .'元',
                    $expend['count_charge']+$expend['count_order_charge'].'笔'
                ],
                [
                    '佣金',
                    bcsub($expend['number_brokerage'],$expend['number_refund_brokerage'],2) .'元',
                    $expend['count_brokerage'] + $expend['count_refund_brokerage'].'笔'
                ],
                [
                    '商户退款',
                    $expend['number_refund'] .'元',
                    $expend['count_refund'].'笔'
                ],
                [
                    '退还优惠券补贴',
                    $expend['number_coupon'] .'元',
                    $expend['count_coupon'].'笔'
                ],
                [
                    '退还会员优惠券补贴',
                    $expend['number_svipcoupon'] .'元',
                    $expend['count_svipcoupon'].'笔'
                ],
            ]
        ];
        $data['charge'] = [
            'title' => '应入账总金额',
            'number' => $charge ,
            'count' => '',
            'data' => []
        ];

        return $data;
    }

    /**
     * TODO 总收入
     * @param $type
     * @param $date
     * @return array
     * @author Qinii
     * @day 3/23/21
     */
    public function countIncome($type, $where, $date)
    {
        $financialType = ['order','order_presell','presell','mer_presell'];
        [$data['count_order'],$data['number_order']] = $this->dao->getDataByType($type, $where, $date, $financialType);
        if ($where['is_mer']){
            $financialType = ['order_platform_coupon'];
        } else {
            $financialType = ['refund_platform_coupon'];
        }
        [ $data['count_coupon'], $data['number_coupon']] = $this->dao->getDataByType($type, $where, $date, $financialType);

        if ($where['is_mer']){
            $financialType = ['order_svip_coupon'];
        } else {
            $financialType = ['refund_svip_coupon'];
        }
        [ $data['count_svipcoupon'], $data['number_svipcoupon']] = $this->dao->getDataByType($type, $where, $date, $financialType);

        $data['count']  = $data['count_order'];
        $data['number'] = bcadd($data['number_coupon'],$data['number_order'],2);

        return $data;
    }

    /**
     * TODO 充值金额
     * @param $type
     * @param $date
     * @return array
     * @author Qinii
     * @day 3/23/21
     */
    public function countBill($type, $date)
    {
        $bill_where = [
            'pm' => 1,
            'status' => 1,
            'category' => 'now_money',
        ];
        $query = app()->make(UserBillRepository::class)->search($bill_where)->where('type','in',['sys_inc_money','recharge']);
        //充值消费金额
        if($type == 1) $query->whereDay('create_time', $date);
        if($type == 2) $query->whereMonth('create_time',$date);

        $count = $query->count();
        $number = $query->sum('number');

        return compact('count','number');
    }

    /**
     * TODO 平台总支出
     * @param $type
     * @param $date
     * @return array
     * @author Qinii
     * @day 3/23/21
     */
    public function countExpend($type, $where, $date)
    {
        /**
         * 平台支出
         * 商户的收入 order_true + 佣金 brokerage_one,brokerage_two + 手续费 refund_charge + 商户预售收入 presell_true
         *
         * 商户支出
         * 退回收入 refund_order + （佣金 brokerage_one,brokerage_two  -  退回佣金 refund_brokerage_two,refund_brokerage_one ） + （手续费  order_charge + 预售手续费 presell_charge  - 平台退给商户的手续费 refund_charge ）
         */
        // 退回佣金
        $financialType = ['brokerage_one','brokerage_two'];
        [$data['count_brokerage'],$data['number_brokerage']] = $this->dao->getDataByType($type, $where, $date, $financialType);

        // 退回 手续费
        $financialType = ['refund_charge'];
        [$data['count_charge'],$data['number_charge']] = $this->dao->getDataByType($type, $where, $date, $financialType);

        if($where['is_mer']){ //商户的
            //退回 收入
            $financialType = ['refund_order'];
            [$data['count_refund'],$data['number_refund']] = $this->dao->getDataByType($type, $where, $date, $financialType);

            //平台手续费
            $financialType = ['order_charge','presell_charge'];
            [$data['count_order_charge'],$data['number_order_charge']] = $this->dao->getDataByType($type, $where, $date, $financialType);

            //退回佣金
            $financialType = ['refund_brokerage_two','refund_brokerage_one'];
            [$data['count_refund_brokerage'],$data['number_refund_brokerage']] = $this->dao->getDataByType($type, $where, $date, $financialType);

            //退回给平台的优惠券金额
            $financialType = ['refund_platform_coupon'];
            [$data['count_coupon'], $data['number_coupon']] = $this->dao->getDataByType($type, $where, $date, $financialType);
            //退回给平台的会员优惠券金额
            $financialType = ['refund_svip_coupon'];
            [$data['count_svipcoupon'], $data['number_svipcoupon']] = $this->dao->getDataByType($type, $where, $date, $financialType);

            //佣金 brokerage_one,brokerage_two  -  退回佣金 refund_brokerage_two,refund_brokerage_one ）
            $number = bcsub($data['number_brokerage'],$data['number_refund_brokerage'],3);

            //平台手续费 =（ order_charge + 预售手续费 presell_charge  - 平台退给商户的手续费 refund_charge ）
            $number_1 = bcsub($data['number_order_charge'],$data['number_charge'],3);

            //退回收入 refund_order + 退回佣金
            $number_2 = bcadd(bcadd($data['number_refund'],$data['number_coupon'],2),$data['number_svipcoupon'],2);

            $data['count']  = $data['count_brokerage'] + $data['count_refund'] + $data['count_order_charge'] + $data['count_refund'] + $data['count_refund_brokerage'] + $data['count_svipcoupon'];
            $data['number'] =bcadd(bcadd($number_2,$number,3),$number_1,2);

        }else{ //平台的
            // 退回 订单实际获得金额

            $financialType = ['order_true','presell_true'];
            [$data['count_order'],$data['number_order']] = $this->dao->getDataByType($type, $where, $date, $financialType);

            //付给商户的优惠券抵扣金额
            $financialType = ['order_platform_coupon'];
            [$data['count_coupon'], $data['number_coupon']] = $this->dao->getDataByType($type, $where, $date, $financialType);

            //付给商户的svip优惠券抵扣金额
            $financialType = ['order_svip_coupon'];
            [$data['count_svipcoupon'], $data['number_svipcoupon']] = $this->dao->getDataByType($type, $where, $date, $financialType);

            $number = bcadd($data['number_brokerage'],$data['number_order'],2);
            $number_1 = bcadd(bcadd($number,$data['number_coupon'],2),$data['number_svipcoupon'],2);

            $data['count']  = $data['count_brokerage'] + $data['count_order'] + $data['count_charge'];
            $data['number'] = bcadd($number_1,$data['number_charge'],2);
        }

        return $data;
    }

    /**
     * TODO 手续费
     * @param $where
     * @param $date
     * @return mixed
     * @author Qinii
     * @day 3/24/21
     */
    public function countCharge($type,$where,$date)
    {
        $financialType = ['order_charge'];
        [$count, $number] = $this->dao->getDataByType($type, $where, $date, $financialType);

        return compact('count','number');
    }

    /**
     * TODO 退款
     * @param $where
     * @param $date
     * @return mixed
     * @author Qinii
     * @day 3/24/21
     */
    public function countRefund($type,$where,$date)
    {
        $financialType = ['refund_order'];
        [$count, $number] = $this->dao->getDataByType($type, $where, $date, $financialType);

        return compact('count','number');
    }
}
