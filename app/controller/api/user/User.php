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


use app\common\repositories\store\IntegralRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\common\repositories\system\CacheRepository;
use app\common\repositories\user\MemberinterestsRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserBrokerageRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\user\UserVisitRepository;
use app\validate\api\UserBaseInfoValidate;
use crmeb\basic\BaseController;
use crmeb\services\MiniProgramService;
use crmeb\services\SmsService;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class User extends BaseController
{
    protected $repository;
    protected $user;

    public function __construct(App $app, UserRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->user = $this->request->userInfo();
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020/6/22
     */
    public function spread_image()
    {
        $type = $this->request->param('type');
        $res = $type == 'routine'
            ? $this->repository->routineSpreadImage($this->user)
            : $this->repository->wxSpreadImage($this->user);
        return app('json')->success($res);
    }

    public function spread_image_v2()
    {
        $type = $this->request->param('type');
        $user = $this->user;
        $siteName = systemConfig('site_name');
        $qrcode = $type == 'routine'
            ? $this->repository->mpQrcode($user)
            : $this->repository->wxQrcode($user);
        $poster = systemGroupData('spread_banner');
        $nickname = $user['nickname'];
        $mark = '邀请您加入' . $siteName;
        return app('json')->success(compact('qrcode', 'poster', 'nickname', 'mark'));
    }

    public function spread_info()
    {
        $user = $this->user;
        if (!$user->is_promoter) return app('json')->fail('您还不是分销员');
        $make = app()->make(UserBrokerageRepository::class);
        $user->append(['one_level_count', 'lock_brokerage', 'two_level_count', 'spread_total', 'yesterday_brokerage', 'total_extract', 'total_brokerage', 'total_brokerage_price']);
        $show_brokerage = (bool)$make->search(['type' => 0])->count();
        $data = [
            'total_brokerage_price' => $user->total_brokerage_price,
            'lock_brokerage' => $user->lock_brokerage,
            'one_level_count' => $user->one_level_count,
            'two_level_count' => $user->two_level_count,
            'spread_total' => $user->spread_total,
            'yesterday_brokerage' => $user->yesterday_brokerage,
            'total_extract' => $user->total_extract,
            'total_brokerage' => $user->total_brokerage,
            'brokerage_price' => $user->brokerage_price,
            'show_brokerage' => $show_brokerage,
            'brokerage' => $show_brokerage ? ($user->brokerage ?: ['brokerage_level' => 0, 'brokerage_name' => '普通分销员']) : null,
            'now_money' => $user->now_money,
            'broken_day' => (int)systemConfig('lock_brokerage_timer'),
            'user_extract_min' => (int)systemConfig('user_extract_min'),
        ];
        return app('json')->success($data);
    }

    public function brokerage_all()
    {
        return app('json')->success(app()->make(UserBrokerageRepository::class)->all(0));
    }

    public function brokerage_info()
    {
        $make = app()->make(UserBrokerageRepository::class);

        $user = $this->user;
        $brokerage = $user->brokerage;
        $next_brokerage = $make->getNextLevel($user->brokerage_level) ?: $brokerage;
        $brokerage_rate = null;
        if ($next_brokerage || $brokerage) {
            $brokerage_rate = $make->getLevelRate($user, $next_brokerage);
        }
        $down_brokerage = null;
        if ($next_brokerage) {
            $down_brokerage = $make->getNextLevel($next_brokerage->brokerage_level);
        }
        $brokerage = $brokerage ?: ['brokerage_level' => 0, 'brokerage_name' => '普通分销员'];
        return app('json')->success(compact('brokerage', 'next_brokerage', 'brokerage_rate', 'down_brokerage'));
    }

    public function brokerage_notice()
    {
        $user = $this->user;
        if (!$user->brokerage_level) {
            return app('json')->fail('无需通知');
        }
        $make = app()->make(CacheRepository::class);
        $key = 'notice_' . $user->uid . '_' . $user->brokerage_level;
        if ($make->getResult($key)) {
            return app('json')->fail('已通知');
        }
        $make->create(['key' => $key, 'result' => 1, 'expire_time' => 0]);
        $userBrokerageRepository = app()->make(UserBrokerageRepository::class);
        return app('json')->success(['type' => $userBrokerageRepository->getNextLevel($user->brokerage_level) ? 'level' : 'top']);
    }

    /**
     * @param UserBillRepository $billRepository
     * @return mixed
     * @author xaboy
     * @day 2020/6/22
     */
    public function bill(UserBillRepository $billRepository)
    {
        [$page, $limit] = $this->getPage();
        return app('json')->success($billRepository->userList([
            'now_money' => $this->request->param('type', 0),
            'status' => 1,
        ], $this->request->uid(), $page, $limit));
    }

    /**
     * @param UserBillRepository $billRepository
     * @return mixed
     * @author xaboy
     * @day 2020/6/22
     */
    public function brokerage_list(UserBillRepository $billRepository)
    {
        [$page, $limit] = $this->getPage();
        [$start,$stop]= $this->request->params(['start','stop'],true);
        $where['date'] = $start&&$stop ? date('Y/m/d',$start).'-'.date('Y/m/d',$stop) : '';
        $where['category'] = 'brokerage';
        return app('json')->success($billRepository->userList($where, $this->request->uid(), $page, $limit));
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/22
     */
    public function spread_order()
    {
        [$page, $limit] = $this->getPage();
        $where= $this->request->params(['keyword']);
        [$start,$stop]= $this->request->params(['start','stop'],true);
        $where['create_time'] = $start&&$stop ? date('Y/m/d',$start).'-'.date('Y/m/d',$stop) : '';
        $data = $this->repository->subOrder($this->request->uid(), $page, $limit,$where);
        return app('json')->success($data);
    }

    /**
     * TODO
     * @return mixed
     * @author Qinii
     * @day 2020-06-18
     */
    public function binding()
    {
        $data = $this->request->params(['phone', 'sms_code']);
        $sms_code = app()->make(SmsService::class)->checkSmsCode($data['phone'], $data['sms_code'], 'binding');
        if (!$data['sms_code'] || !$sms_code)
            return app('json')->fail('验证码不正确');
        $user = $this->repository->accountByUser($data['phone']);
        if ($user) {
            if (systemConfig('is_phone_login') === '1') {
                return app('json')->fail('手机号已被绑定');
            }
            $data = ['phone' => $data['phone']];
        } else {
            $data = ['account' => $data['phone'], 'phone' => $data['phone']];
        }
        $this->repository->update($this->request->uid(), $data);
        return app('json')->success('绑定成功');
    }

    /**
     * TODO 小程序获取手机号绑定
     * @author Qinii
     * @day 10/11/21
     */
    public function mpPhone()
    {
        $code = $this->request->param('code');
        $iv = $this->request->param('iv');
        $encryptedData = $this->request->param('encryptedData');
        $miniProgramService = MiniProgramService::create();
        $userInfoCong = $miniProgramService->getUserInfo($code);
        $session_key = $userInfoCong['session_key'];

        $data = $miniProgramService->encryptor($session_key, $iv, $encryptedData);

        $user = $this->repository->accountByUser($data['purePhoneNumber']);
        if ($user) {
            $data = ['phone' => $data['purePhoneNumber']];
        } else {
            $data = ['account' => $data['purePhoneNumber'], 'phone' => $data['purePhoneNumber']];
        }
        $this->repository->update($this->request->uid(), $data);
        return app('json')->success('绑定成功');
    }
    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/22
     */
    public function spread_list()
    {

        $where = $this->request->params([ 'sort', 'keyword']);
        [$start,$stop]= $this->request->params(['start','stop'],true);
        $where['spread_time'] = $start&&$stop ? date('Y/m/d',$start).'-'.date('Y/m/d',$stop) : '';
        $level = $this->request->param('level');
        [$page, $limit] = $this->getPage();
        return app('json')->success($level == 2
            ? $this->repository->getTwoLevelList($this->request->uid(),$where, $page, $limit)
            : $this->repository->getOneLevelList($this->request->uid(),$where, $page, $limit));
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020/6/22
     */
    public function spread_top()
    {
        [$page, $limit] = $this->getPage();
        $type = $this->request->param('type', 0);
        $func = $type == 1 ? 'spreadMonthTop' : 'spreadWeekTop';
        $data = $this->repository->{$func}($page, $limit);
        return app('json')->success($data);
    }

    /**
     * @return mixed
     * @author xaboy
     * @day 2020/6/22
     */
    public function brokerage_top()
    {
        [$page, $limit] = $this->getPage();
        $type = $this->request->param('type', 'week');
        $uid = $this->request->uid();
        $func = $type == 'month' ? 'brokerageMonthTop' : 'brokerageWeekTop';
        $data = $this->repository->{$func}($uid, $page, $limit);
        return app('json')->success($data);
    }

    public function history(UserVisitRepository $repository)
    {
        $uid = $this->request->uid();
        [$page, $limit] = $this->getPage();
        return app('json')->success($repository->getHistory($uid, $page, $limit));
    }

    public function deleteHistory($id, UserVisitRepository $repository)
    {
        $uid = $this->request->uid();

        if (!$repository->getWhereCount(['user_visit_id' => $id, 'uid' => $uid]))
            return app('json')->fail('数据不存在');
        $repository->delete($id);
        return app('json')->success('删除成功');
    }

    public function deleteHistoryBatch(UserVisitRepository $repository)
    {
        $uid = $this->request->uid();
        $data = $this->request->param('ids');
        if (!empty($data) && is_array($data)) {
            foreach ($data as $id) {
                if (!$repository->getWhereCount(['user_visit_id' => $id, 'uid' => $uid]))
                    return app('json')->fail('数据不存在');
            }
            $repository->batchDelete($data, null);
        }
        if ($data == 1)
            $repository->batchDelete(null, $uid);

        return app('json')->success('删除成功');
    }

    public function account()
    {
        $user = $this->user;
        if (!$user->phone) return app('json')->fail('请绑定手机号');
        return app('json')->success($this->repository->selfUserList($user->phone));
    }

    public function switchUser()
    {
        $uid = (int)$this->request->param('uid');
        if (!$uid) return app('json')->fail('用户不存在');
        $userInfo = $this->user;
        if (!$userInfo->phone) return app('json')->fail('请绑定手机号');
        $user = $this->repository->switchUser($userInfo, $uid);
        $tokenInfo = $this->repository->createToken($user);
        $this->repository->loginAfter($user);
        return app('json')->success($this->repository->returnToken($user, $tokenInfo));
    }

    public function edit()
    {
        $data = $this->request->params(['avatar', 'nickname']);
        $uid = (int)$this->request->param('uid');
        if (!$uid) return app('json')->fail('用户不存在');

        if (empty($data['avatar'])) unset($data['avatar']);
        if (empty($data['nickname'])) unset($data['nickname']);
        if (empty($data)) return app('json')->fail('参数丢失');
        $this->repository->update($this->request->uid(), $data);

        return app('json')->success('修改成功');
    }

    public function changePassword()
    {
        $data = $this->request->params(['repassword','password', 'sms_code']);

        if (!$this->user->phone)
            return app('json')->fail('请先绑定手机号');
        if (empty($data['repassword']) || empty($data['password']))
            return app('json')->fail('请输入密码');
        if ($data['repassword'] !== $data['password'])
            return app('json')->fail('两次密码不一致');

        $sms_code = app()->make(SmsService::class)->checkSmsCode($this->user->phone, $data['sms_code'], 'change_pwd');
        if (!$data['sms_code'] || !$sms_code)
            return app('json')->fail('验证码不正确');

        $password = $this->repository->encodePassword($data['password']);
        $this->repository->update($this->request->uid(), ['pwd' => $password]);
        return app('json')->success('绑定成功');
    }

    public function changePhone()
    {
        $data = $this->request->params(['phone', 'sms_code']);
        $sms_code = app()->make(SmsService::class)->checkSmsCode($data['phone'], $data['sms_code'], 'change_phone');
        if (!$data['sms_code'] || !$sms_code)
            return app('json')->fail('验证码不正确');
        $user = $this->repository->accountByUser($data['phone']);
        $data['main_uid'] = 0;
        if ($user) {
            if ($this->request->userInfo()->account !== $data['phone']) {
                $data['account'] = $this->request->userInfo()->account.'_'.$this->request->uid();
            }
        } else {
            $data['account'] = $data['phone'];
        }
        unset($data['sms_code']);
        $this->repository->update($this->request->uid(), $data);
        return app('json')->success('修改成功');
    }


    public function getAgree($key)
    {
        $make = app()->make(CacheRepository::class);
        $data = $make->getResult($key);
        return app('json')->success($data);
    }

    public function integralInfo(UserBillRepository $make)
    {
        if (!systemConfig('integral_status')) {
            return app('json')->fail('积分功能未开启');
        }

        $integral = $this->user->integral;
        $lockIntegral = $make->lockIntegral($this->user->uid);
        $deductionIntegral = $make->deductionIntegral($this->user->uid);
        $totalGainIntegral = $make->totalGainIntegral($this->user->uid);
        $make1 = app()->make(IntegralRepository::class);
        $nextClearDay = $make1->getTimeoutDay();
        $status = $nextClearDay < strtotime('+20 day');
        $invalidDay = $make1->getInvalidDay();
        if ($status && $integral > 0 && $invalidDay) {
            $validIntegral = $make->validIntegral($this->user->uid, date('Y-m-d H:i:s', $invalidDay), date('Y-m-d H:i:s', $nextClearDay));
            if ($integral > $validIntegral) {
                $nextClearIntegral = (int)bcsub($integral, $validIntegral, 0);
            } else {
                $nextClearIntegral = 0;
            }
        } else {
            $nextClearIntegral = 0;
        }
        $nextClearDay = date('m月d日', $nextClearDay);
        $clear = compact('nextClearDay', 'status', 'nextClearIntegral');

        return app('json')->success(compact('integral', 'lockIntegral', 'deductionIntegral', 'totalGainIntegral', 'clear'));
    }

    public function integralList(UserBillRepository $repository)
    {
        if (!systemConfig('integral_status')) {
            return app('json')->fail('积分功能未开启');
        }
        [$page, $limit] = $this->getPage();
        $data = $repository->userList(['category' => 'integral'], $this->user->uid, $page, $limit);
        return app('json')->success($data);
    }

    public function services()
    {
        $uid = $this->user->uid;
        $where = $this->request->params(['is_verify', 'customer', 'is_goods', ['is_open',1]]);
        $is_sys = $this->request->param('is_sys');
        return app('json')->success(app()->make(StoreServiceRepository::class)->getServices($uid, $where,$is_sys));
    }

    public function memberInfo()
    {
        if (!systemConfig('member_status')) return app('json')->fail('未开启会员功能');
        $make = app()->make(UserBrokerageRepository::class);
        $data['uid'] = $this->user->uid;
        $data['avatar'] = $this->user->avatar;
        $data['nickname'] = $this->user->nickname;
        $data['member_value'] = $this->user->member_value;
        $data['member'] = $this->user->member;
        $next_level = $make->getNextLevel($this->user->member_level, 1);
        if (!$next_level &&  $data['member']) {
            $next_level = $this->user->member->toArray();
            $next_level['brokerage_rule']['value'] = 0;
        }
        $data['next_level'] = $next_level;

        $makeInteres = app()->make(MemberinterestsRepository::class);
        $data['interests'] =  systemConfig('member_interests_status') ? $makeInteres->getInterestsByLevel($makeInteres::TYPE_FREE,$this->user->member_level) : [] ;

        $data['today'] = app()->make(UserBillRepository::class)->search([
            'category' => 'sys_members',
            'uid' => $this->user->uid,
            'day' => date('Y-m-d', time())
        ])->sum('number');

        $config_key = ['member_pay_num', 'member_sign_num', 'member_reply_num', 'member_share_num'];
        if (systemConfig('community_status')) $config_key[] = 'member_community_num';

        $config= systemConfig($config_key);
        if ($this->user->is_svip > 0) {
            foreach ($config as $key => $item) {
                $data['config'][$key] = $item .' x' . $makeInteres->getSvipInterestVal($makeInteres::HAS_TYPE_MEMBER).' ';
            }
        } else {
            $data['config'] = $config;
        }

        return app('json')->success($data);
    }

    public function notice()
    {
        $type = $this->request->param('type',0);
        $arr = [
            '0' => 'brokerage_level',
            '1' => 'member_level',
        ];
        $filed = $arr[$type];
        if (!$this->user->$filed) {
            return app('json')->fail('无需通知');
        }

        $make = app()->make(CacheRepository::class);
        $key = 'notice_' . $filed . '_' . $this->user->uid;
        if ($ret = $make->getWhere(['key' => $key])) {
            $userBrokerageRepository = app()->make(UserBrokerageRepository::class);
            $level = app()->make(UserBrokerageRepository::class)->getWhere(
                ['brokerage_level' => $ret->result, 'type' => $type],
                'brokerage_name,brokerage_icon,brokerage_rule'
            );
            $next_level = $userBrokerageRepository->getNextLevel($this->user->$filed, $type);
            $ret->delete();
            $type = $next_level ? 'level' : 'top';
            return app('json')->success(compact('type', 'level'));
        }
        return app('json')->fail('已通知');
    }

    public function updateBaseInfo(UserBaseInfoValidate $validate)
    {
        if (systemConfig('open_update_info') != '1') {
            return app('json')->fail('不允许修改基本信息');
        }
        $nickname = $this->request->param('nickname');
        $avatar = $this->request->param('avatar');
        if (!$nickname && !$avatar)
            return app('json')->fail('未做任何修改');
        $user = $this->request->userInfo();
        if(!empty($nickname)) {
            $validate->check(['nickname' => $nickname]);
            $data['nickname'] = $nickname;
        }
        if(!empty($avatar)) {
            $data['avatar'] = $avatar;
        }
        $this->repository->updateBaseInfo($data,$user);
        return app('json')->success('修改成功');
    }

}
