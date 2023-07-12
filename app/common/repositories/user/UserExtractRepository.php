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

use app\common\repositories\BaseRepository;
use app\common\dao\user\UserExtractDao as dao;
use app\common\repositories\wechat\WechatUserRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\services\MiniProgramService;
use crmeb\services\SwooleTaskService;
use crmeb\services\WechatService;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Queue;

class UserExtractRepository extends BaseRepository
{

    /**
     * @var dao
     */
    protected $dao;


    /**
     * UserExtractRepository constructor.
     * @param dao $dao
     */
    public function __construct(dao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * TODO
     * @param $id
     * @return bool
     * @author Qinii
     * @day 2020-06-16
     */
    public function getWhereCount($id)
    {
        $where['extract_id'] = $id;
        $where['status'] = 0;
        return $this->dao->getWhereCount($where) > 0;
    }

    /**
     * TODO
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @author Qinii
     * @day 2020-06-16
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->with(['user' => function ($query) {
            $query->field('uid,avatar,nickname');
        }]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function getTotalExtractPrice()
    {
        return $this->dao->search(['status' => 1])->sum('extract_price');
    }

    /**
     * @param $uid
     * @return mixed
     * @author xaboy
     * @day 2020/6/22
     */
    public function userTotalExtract($uid)
    {
        return $this->dao->search(['status' => 1, 'uid' => $uid])->sum('extract_price');
    }

    /**
     * TODO
     * @param $user
     * @param $data
     * @author Qinii
     * @day 2020-06-16
     */
    public function create($user,$data)
    {
        event('user.extract.before',compact('user','data'));
        $userExtract = Db::transaction(function()use($user,$data){
            if($user['brokerage_price'] < (systemConfig('user_extract_min')))
                throw new ValidateException('可提现金额不足');
            if($data['extract_price'] < (systemConfig('user_extract_min')))
                throw new ValidateException('提现金额不得小于最低额度');
            if($user['brokerage_price'] < $data['extract_price'])
                throw new ValidateException('提现金额不足');
            if($data['extract_type'] == 3) {
                $make = app()->make(WechatUserRepository::class);
                $openid = $make->idByOpenId((int)$user['wechat_user_id']);
                if (!$openid){
                    $openid = $make->idByRoutineId((int)$user['wechat_user_id']);
                    if(!$openid) throw new ValidateException('openID获取失败,请确认是微信用户');
                }
            }
            $brokerage_price = bcsub($user['brokerage_price'],$data['extract_price'],2);
            $user->brokerage_price = $brokerage_price;
            $user->save();

            $data['extract_sn'] = $this->createSn();
            $data['uid'] = $user['uid'];
            $data['balance'] = $brokerage_price;

            return $this->dao->create($data);
        });
        event('user.extract',compact('userExtract'));
        SwooleTaskService::admin('notice', [
            'type' => 'extract',
            'title' => '您有一条新的提醒申请',
            'id' => $userExtract->extract_id
        ]);
    }

    public function switchStatus($id,$data)
    {
        $extract = $this->dao->getWhere(['extract_id' => $id]);
        $user = app()->make(UserRepository::class)->get($extract['uid']);
        if(!$user) throw new ValidateException('用户不存在');
        $brokerage_price = 0;
        if($data['status'] == -1)
            $brokerage_price = bcadd($user['brokerage_price'] ,$extract['extract_price'],2);
        $type = systemConfig('sys_extension_type');
        $ret = [];
        $service = null;
        $func = null;
        if ($data['status'] == 1 && $extract['extract_type'] == 3 && in_array($type,[1,2])) {
            $func = $type == 1 ? 'merchantPay' : 'companyPay';
            $ret = [
                'sn' => $extract['extract_sn'],
                'price' => $extract['extract_price'],
                'mark' => '企业付款给用户:'.$user->nickname,
                'batch_name' => '企业付款给用户:'.$user->nickname
            ];
            $openid = app()->make(WechatUserRepository::class)->idByOpenId((int)$user['wechat_user_id']);
            if ($openid) {
                $ret['openid'] = $openid;
                $service = WechatService::create();
            } else {
                $routineOpenid = app()->make(WechatUserRepository::class)->idByRoutineId((int)$user['wechat_user_id']);
                if (!$routineOpenid) throw new ValidateException('非微信用户不支持付款到零钱');
                $ret['openid'] = $routineOpenid;
                $service =  MiniProgramService::create();
            }
        }

        Db::transaction(function()use($id,$data,$user,$brokerage_price,$ret,$service,$func){
            event('user.extractStatus.before',compact('id','data'));
            if ($ret) $service->{$func}($ret);
            if($brokerage_price){
                $user->brokerage_price = $brokerage_price;
                $user->save();
            }
            $userExtract = $this->dao->update($id,$data);
            event('user.extractStatus',compact('id','userExtract'));
        });

        Queue::push(SendSmsJob::class,['tempId' => 'EXTRACT_NOTICE', 'id' =>$id]);
    }

    public function createSn()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = number_format((floatval($msec) + floatval($sec)) * 1000, 0, '', '');
        $sn = 'ue' . $msectime . mt_rand(10000, max(intval($msec * 10000) + 10000, 98369));
        return $sn;
    }

    public function getHistoryBank($uid)
    {
        return $this->dao->getSearch(['uid' => $uid,'extract_type' => 0])->order('create_time DESC')->field('real_name,bank_code,bank_address,bank_name')->find();
    }
}
