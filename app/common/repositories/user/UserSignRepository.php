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


use app\common\dao\user\UserSignDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\groupData\GroupDataRepository;
use app\common\repositories\system\groupData\GroupRepository;
use think\exception\ValidateException;
use think\facade\Db;


class UserSignRepository extends BaseRepository
{
    /**
     * @var UserSignDao
     */
    protected $dao;

    /**
     * UserSignRepository constructor.
     * @param UserSignDao $dao
     */
    public function __construct(UserSignDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 获取指定日期 用户的连续签到数
     * @param int $uid
     * @param string $day
     * @return array
     * @author Qinii
     * @day 6/8/21
     */
    public function getSign(int $uid,string $day)
    {
        return $this->dao->getSearch(['uid' => $uid,'day' => $day])->value('sign_num');
    }

    public function getDay(int $num)
    {
        if($num > 7) {
            $yu = ($num % 7);
            $num = ($yu == 0) ? 6 : $yu - 1;
        } else {
            $num = (($num -1) < 0) ? 0 : ($num -1);
        }

        $title = $this->signConfig();
        if(empty($title)) throw new ValidateException('未开启签到功能');
        if (isset($title[$num]['value'])) {
            $dat = $title[$num]['value'];
        } else {
            $dat = [
                'sign_day' => '无',
                'sign_integral' => 0,
            ];
        }
        return $dat;
    }

    /**
     * TODO 签到操作
     * @param int $uid
     * @author Qinii
     * @day 6/8/21
     */
    public function create(int $uid)
    {
        /**
         *  用户昨天的签到情况,如果有就是连续签到，如果没有就是第一天签到
         *  根据签到天数计算签到积分等操作
         *  计算用户剩余积分
         *
         */
        $yesterday = date("Y-m-d",strtotime("-1 day"));
        $sign_num = ($this->getSign($uid,$yesterday) ?: 0) + 1;
        //签到规则计算
        $sign_task = $this->getDay($sign_num);
        $user = app()->make(UserRepository::class)->get($uid);
        $integral = $sign_task['sign_integral'];
        if ($user->is_svip > 0) {
            $makeInteres = app()->make(MemberinterestsRepository::class);
            $integral = $integral * $makeInteres->getSvipInterestVal($makeInteres::HAS_TYPE_SIGN);;
        }
        $user_make = app()->make(UserRepository::class);
        $user = $user_make->get($uid);
        $integral_ = $user['integral'] + $integral;
        $data = [
            'uid'      => $uid,
            'sign_num' => $sign_num,
            'number'   => $integral,
            'integral' => $integral_,
            'title'    =>   '签到',
        ];
        //增加记录
        $arr = [
            'status' => 1,
            'mark'   => '签到,获得积分'. $integral,
            'number' => $integral,
            'balance'=> $integral_,
        ];
        return Db::transaction(function() use($uid,$data,$user_make,$sign_task,$arr,$integral){
           $ret = $this->dao->create($data);
            $user_make->incIntegral($uid,$integral,'签到'.$sign_task['sign_day'],'sign_integral',$arr);
            app()->make(UserBrokerageRepository::class)->incMemberValue($uid, 'member_sign_num', $ret->sign_id);
            return compact('integral');
        });
    }

    public function getList(array $where,int $page,int $limit)
    {
        $query = $this->dao->getSearch($where)->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page,$limit)->select();
        return compact('count','list');
    }

    public function info(int $uid)
    {
        /**
         *  连续签到日期展示 1 - 7天
         *  是否签到
         *  累计签到数
         */

        $ret = $this->signStatus($uid);
        $is_sign = $ret['is_sign'];
        $sign_num = $ret['sign_num'];
        $title = $this->signConfig();
        $userInfo = app()->make(UserRepository::class)->getWhere(['uid' => $uid],'uid,avatar,nickname,integral');
        $count = $this->dao->getSearch(['uid' => $uid])->count('*');
        return compact('userInfo','is_sign','sign_num','count','title');

    }

    public function signConfig(){
        $group_make = app()->make(GroupRepository::class);
        $sign_day_config = $group_make->keyById('sign_day_config');
        $title = app()->make(GroupDataRepository::class)
            ->getGroupDataWhere(0,$sign_day_config)
            ->where('status',1)->limit(7)
            ->hidden(['group_data_id','group_id','create_time','mer_id'])->select()->toArray();
        return $title;
    }

    /**
     * TODO 连续签到 获取 1- 7 天
     * @param $uid
     * @return array
     * @author Qinii
     * @day 6/10/21
     */
    public function signStatus($uid)
    {
        $day = date('Y-m-d',time());
        $sign_num = 0;
        $sign_num = $this->getSign($uid,$day);
        $is_sign = $sign_num ? 1 : 0;

        if($sign_num > 7){
            $sign_num = ($sign_num % 7);
            if(!$sign_num) $sign_num = 7;
        }

        if(!$is_sign){
            $yesterday = date("Y-m-d",strtotime("-1 day"));
            $sign_num = $this->getSign($uid,$yesterday) ?: 0;
            if($sign_num > 7){
                $sign_num = ($sign_num % 7);
            }
        }
        return compact('is_sign','sign_num');
    }

    /**
     * TODO 按月显示签到记录
     * @param array $where
     * @return array
     * @author Qinii
     * @day 6/10/21
     */
    public function month(array $where)
    {
        $group = $this->dao->getSearch($where)->field('FROM_UNIXTIME(unix_timestamp(create_time),"%Y-%m") as time')
            ->order('time DESC')->group('time')->select();
        $ret = [];
        foreach ($group as $k => $item){
            $ret[$k]['month'] = $item['time'];
            $query = $this->dao->getSearch($where)->field('title,number,create_time')->whereMonth('create_time',$item['time']);
            $ret[$k]['list'] = $query->order('create_time DESC')->select();
        }
        return $ret;
    }
}
