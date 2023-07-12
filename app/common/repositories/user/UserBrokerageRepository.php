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


use app\common\dao\user\UserBrokerageDao;
use app\common\model\user\User;
use app\common\model\user\UserBrokerage;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\CacheRepository;
use FormBuilder\Factory\Elm;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route;

/**
 * @mixin UserBrokerageDao
 */
class UserBrokerageRepository extends BaseRepository
{

    public const BROKERAGE_RULE_TYPE = ['spread_user', 'pay_money', 'pay_num', 'spread_money', 'spread_pay_num'];

    public function __construct(UserBrokerageDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->order('brokerage_level ASC,create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('list', 'count');
    }

    public function getNextLevel($level,$type = 0)
    {
        return $this->search(['next_level' => $level,'type' => $type])->order('brokerage_level ASC,create_time DESC')->find();
    }

    public function options(array $where)
    {
        return $this->dao->search($where)->field('brokerage_level as value,brokerage_name as label')->order('brokerage_level ASC,create_time DESC')->select();
    }

    public function all(int $type)
    {
        return $this->dao->search(['type' => $type])->order('brokerage_level ASC,create_time DESC')->select();
    }

    public function inc(User $user, $type, $inc)
    {
        $nextLevel = $this->getNextLevel($user->brokerage_level);
        if (!$nextLevel) return false;
        $make = app()->make(UserBillRepository::class);
        $bill = $make->getWhere(['uid' => $user->uid, 'link_id' => $nextLevel->user_brokerage_id, 'category' => 'sys_brokerage', 'type' => $type]);
        if ($bill) {
            $bill->number = bcadd($bill->number, $inc, 2);
            $bill->save();
        } else {
            $make->incBill($user->uid, 'sys_brokerage', $type, [
                'number' => $inc,
                'title' => $type,
                'balance' => 0,
                'status' => 0,
                'link_id' => $nextLevel->user_brokerage_id
            ]);
        }

        return $this->checkLevel($user, $nextLevel);
    }

    public function checkLevel(User $user, UserBrokerage $nextLevel)
    {
        $info = app()->make(UserBillRepository::class)->search(['uid' => $user->uid, 'category' => 'sys_brokerage', 'link_id' => $nextLevel->user_brokerage_id])
            ->column('number', 'type');
        foreach ($nextLevel['brokerage_rule'] as $k => $rule) {
            if (!isset($info[$k]) && $rule['num'] > 0) return false;
            if ($rule['num'] > 0 && $rule['num'] > $info[$k]) return false;
        }
        $nextLevel->user_num++;
        Db::transaction(function () use ($nextLevel, $user) {
            $nextLevel->save();
            if ($user->brokerage && $user->brokerage->user_num > 0) {
                $user->brokerage->user_num--;
                $user->brokerage->save();
            }
            $user->brokerage_level = $nextLevel->brokerage_level;
            $user->save();

            $key = 'notice_brokerage_level_' . $user->uid;
            app()->make(CacheRepository::class)->save($key,$nextLevel->brokerage_level);
        });
        return true;
    }

    public function getLevelRate(User $user, UserBrokerage $nextLevel)
    {
        $info = app()->make(UserBillRepository::class)->search(['uid' => $user->uid, 'category' => 'sys_brokerage', 'link_id' => $nextLevel->user_brokerage_id])
            ->column('number', 'type');
        $brokerage_rule = $nextLevel['brokerage_rule'];
        foreach ($nextLevel['brokerage_rule'] as $k => $rule) {
            if ($rule['num'] <= 0) {
                unset($brokerage_rule[$k]);
                continue;
            }
            if (!isset($info[$k])) {
                $rate = 0;
            } else if ($rule['num'] > $info[$k]) {
                $rate = bcdiv($info[$k], $rule['num'], 2) * 100;
            } else {
                $rate = 100;
            }
            $brokerage_rule[$k]['rate'] = $rate;
            $brokerage_rule[$k]['task'] = (float)(min($info[$k] ?? 0, $rule['num']));
        }
        return $brokerage_rule;
    }

    public function form(?int $id = null)
    {
        $formData = [];
        if ($id) {
            $form = Elm::createForm(Route::buildUrl('systemUserMemberUpdate', ['id' => $id])->build());
            $data = $this->dao->get($id);
            if (!$data) throw new ValidateException('数据不存在');
            $formData = $data->toArray();

        } else {
            $form = Elm::createForm(Route::buildUrl('systemUserMemberCreate')->build());
        }

        $rules = [
            Elm::number('brokerage_level', '会员等级')->required(),
            Elm::input('brokerage_name', '会员名称')->required(),
            Elm::frameImage('brokerage_icon', '会员图标', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=brokerage_icon&type=1')
                ->required()
                ->value($formData['brokerage_icon'] ?? '')
                ->modal(['modal' => false])
                ->width('896px')
                ->height('480px'),
            Elm::number('value', ' 所需成长值',$formData['brokerage_rule']['value'] ?? 0)->required(),
            Elm::frameImage('image', '背景图', '/' . config('admin.admin_prefix') . '/setting/uploadPicture?field=image&type=1')
                ->value($formData['brokerage_rule']['image']??'')
                ->required()
                ->modal(['modal' => false])
                ->width('896px')
                ->height('480px'),
        ];
        $form->setRule($rules);
        return $form->setTitle(is_null($id) ? '添加会员等级' : '编辑会员等级')->formData($formData);
    }

    public function incMemberValue(int $uid, string $type, int $id)
    {
        if (!systemConfig('member_status')) return ;
        $make = app()->make(UserBillRepository::class);
        $count = $make->getWhereCount(['type' => $type, 'link_id' => $id]);
        if ($count) return ;
        $config = [
            'member_pay_num'   => '下单获得成长值',
            'member_sign_num'  => '签到获得成长值',
            'member_reply_num' => '评价获得成长值',
            'member_share_num' => '邀请获得成长值',
            'member_community_num'  => '种草图文获得成长值',
        ];
        $inc = systemConfig($type) > 0 ? systemConfig($type) : 0;
        $user = app()->make(UserRepository::class)->getWhere(['uid' => $uid],'*',['member']);
        $svip_status = $user->is_svip > 0 && systemConfig('svip_switch_status') == '1';
        if ($svip_status) {
            $svipRate = app()->make(MemberinterestsRepository::class)->getSvipInterestVal(MemberinterestsRepository::HAS_TYPE_MEMBER);
            if ($svipRate > 0) {
                $inc = bcmul($svipRate, $inc, 0);
            }
        }
        $this->checkMemberValue($user, $inc);
        $make->incBill($user->uid, 'sys_members', $type, [
            'number'  => $inc,
            'title'   => $config[$type],
            'balance' => $user->member_value,
            'status'  => 0,
            'link_id' => $id,
            'mark' => $config[$type].':'.$inc,
        ]);
    }

    /**
     * TODO 连续升级
     * @param $nextLevel
     * @param $num
     * @return array
     * @author Qinii
     * @day 1/11/22
     */
    public function upUp($nextLevel, $num)
    {
        $newLevel = $this->getNextLevel($nextLevel->brokerage_level, 1);
        if ($newLevel) {
            $newNum = $num - $newLevel->brokerage_rule['value'];
            if ($newNum > 0) {
                [$nextLevel,$num] = $this->upUp($newLevel, $newNum);
            }
        }
        return [$nextLevel,$num];
    }

    /**
     * TODO 升级操作
     * @param User $user
     * @param int $inc
     * @author Qinii
     * @day 1/11/22
     */
    public function checkMemberValue(User $user, int $inc)
    {
        /**
         * 下一级所需经验值
         * 当前的经验值加上增加经验值是否够升级
         */
        Db::transaction(function () use ($inc, $user) {
            $nextLevel = $this->getNextLevel($user->member_level, 1);
            if (!$nextLevel) return ;
            if (($user->member_value + $inc) >= $nextLevel->brokerage_rule['value']) {

                $num = ($user->member_value + $inc) - $nextLevel->brokerage_rule['value'];
                if ($num > 0) {
                    [$nextLevel,$num] = $this->upUp($nextLevel, $num);
                }
                if ($user->member) {
                    $user->member->user_num--;
                    $user->member->save();
                }

                $nextLevel->user_num++;
                $nextLevel->save();

                $user->member_level = $nextLevel->brokerage_level;

                $key = 'notice_member_level_' . $user->uid;
                app()->make(CacheRepository::class)->save($key,$nextLevel->brokerage_level);
            } else {
                $num = ($user->member_value + $inc);
            }
            $user->member_value = $num;
            $user->save();
        });
    }
}
