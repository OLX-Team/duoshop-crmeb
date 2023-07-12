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


namespace app\common\dao\user;


use app\common\dao\BaseDao;
use app\common\model\BaseModel;
use app\common\model\user\User;
use think\Collection;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\Model;

/**
 * Class UserDao
 * @package app\common\dao\user
 * @author xaboy
 * @day 2020-04-28
 */
class UserDao extends BaseDao
{

    /**
     * @return BaseModel
     * @author xaboy
     * @day 2020-03-30
     */
    protected function getModel(): string
    {
        return User::class;
    }

    /**
     * @return string
     * @author xaboy
     * @day 2020/6/22
     */
    public function defaultPwd()
    {
        return substr(md5(time() . random_int(10, 99)), 0, 8);
    }

    /**
     * @param array $where
     * @return BaseQuery
     * @author xaboy
     * @day 2020-05-07
     */
    public function search(array $where)
    {
        if (isset($where['province']) && $where['province'] !== '') {
            $query = User::hasWhere('wechat', function ($query) use ($where) {
                $query->where('province', $where['province']);
                if ($where['city'] !== '') $query->where('city', $where['city']);
            });
        } else {
            $query = User::getDB()->alias('User');
        }
        $query->whereNull('User.cancel_time')
            ->when(isset($where['keyword']) && $where['keyword'], function (BaseQuery $query) use ($where) {
            return $query->where('User.uid|User.real_name|User.nickname|User.phone', 'like', '%' . $where['keyword'] . '%');
        })->when(isset($where['user_type']) && $where['user_type'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.user_type', $where['user_type']);
        })->when(isset($where['uid']) && $where['uid'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.uid', $where['uid']);
        })->when(isset($where['status']) && $where['status'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.status', intval($where['status']));
        })->when(isset($where['group_id']) && $where['group_id'], function (BaseQuery $query) use ($where) {
            return $query->where('User.group_id', intval($where['group_id']));
        })->when(isset($where['brokerage_level']) && $where['brokerage_level'], function (BaseQuery $query) use ($where) {
            return $query->where('User.brokerage_level', intval($where['brokerage_level']));
        })->when(isset($where['label_id']) && $where['label_id'] !== '', function (BaseQuery $query) use ($where) {
            return $query->whereRaw('CONCAT(\',\',User.label_id,\',\') LIKE \'%,' . $where['label_id'] . ',%\'');
        })->when(isset($where['sex']) && $where['sex'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.sex', intval($where['sex']));
        })->when(isset($where['is_promoter']) && $where['is_promoter'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.is_promoter', $where['is_promoter']);
        })->when(isset($where['phone']) && $where['phone'] !== '', function (BaseQuery $query) use ($where) {
            return $query->whereLike('User.phone', "%{$where['phone']}%");
        })->when(isset($where['nickname']) && $where['nickname'] !== '', function (BaseQuery $query) use ($where) {
            return $query->whereLike('User.nickname', "%{$where['nickname']}%");
        })->when(isset($where['spread_time']) && $where['spread_time'] !== '', function (BaseQuery $query) use ($where) {
            getModelTime($query, $where['spread_time'], 'User.spread_time');
        })->when(isset($where['date']) && $where['date'] !== '', function (BaseQuery $query) use ($where) {
            getModelTime($query, $where['date'], 'User.create_time');
        })->when(isset($where['promoter_date']) && $where['promoter_date'] !== '', function (BaseQuery $query) use ($where) {
            getModelTime($query, $where['promoter_date'], 'User.promoter_time');
        })->when(isset($where['spread_uid']) && $where['spread_uid'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.spread_uid', intval($where['spread_uid']));
        })->when(isset($where['spread_uids']), function (BaseQuery $query) use ($where) {
            return $query->whereIn('User.spread_uid', $where['spread_uids']);
        })->when(isset($where['uids']), function (BaseQuery $query) use ($where) {
            return $query->whereIn('User.uid', $where['uids']);
        })->when(isset($where['pay_count']) && $where['pay_count'] !== '', function ($query) use ($where) {
            if ($where['pay_count'] == -1) {
                $query->where('User.pay_count', 0);
            } else {
                $query->where('User.pay_count', '>', $where['pay_count']);
            }
        })->when(isset($where['user_time_type']) && $where['user_time_type'] !== '' && $where['user_time'] != '', function ($query) use ($where) {
            if ($where['user_time_type'] == 'visit') {
                getModelTime($query, $where['user_time'], 'User.last_time');
            }
            if ($where['user_time_type'] == 'add_time') {
                getModelTime($query, $where['user_time'], 'User.create_time');
            }
        })->when(isset($where['sort']) && in_array($where['sort'], ['pay_count ASC', 'pay_count DESC', 'pay_price DESC', 'pay_price ASC', 'spread_count ASC', 'spread_count DESC']), function (BaseQuery $query) use ($where) {
            $query->order('User.' . $where['sort']);
        }, function ($query) {
            $query->order('User.uid DESC');
        })->when(isset($where['is_svip']) && $where['is_svip'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.is_svip','>',0);
        })->when(isset($where['svip_type']) && $where['svip_type'] !== '', function (BaseQuery $query) use ($where) {
            return $query->where('User.is_svip',$where['svip_type']);
        });

        return $query;
    }

    /**
     * @param $keyword
     * @return BaseQuery
     * @author xaboy
     * @day 2020/6/22
     */
    public function searchMerUser($keyword)
    {
        return User::getDB()->whereLike('nickname', "%$keyword%")->whereNull('cancel_time')->where('status', 1);
    }

    /**
     * @param array $ids
     * @param int $group_id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-05-07
     */
    public function batchChangeGroupId(array $ids, int $group_id)
    {
        return User::getDB()->whereIn($this->getPk(), $ids)->update(compact('group_id'));
    }

    /**
     * @param array $ids
     * @param array $label_id
     * @return int
     * @throws DbException
     * @author xaboy
     * @day 2020-05-07
     */
    public function batchChangeLabelId(array $ids, array $label_id)
    {
        $label_id = implode(',', $label_id);
        return User::getDB()->whereIn($this->getPk(), $ids)->update(compact('label_id'));
    }


    /**
     * @param int $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020-04-28
     */
    public function wechatUserIdBytUser(int $id)
    {
        return User::getDB()->where('wechat_user_id', $id)->find();
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020-04-28
     */
    public function wechatUserIdByUid($id)
    {
        return User::getDB()->where('wechat_user_id', $id)->value('uid');
    }

    /**
     * @param $id
     * @return mixed
     * @author xaboy
     * @day 2020/7/7
     */
    public function uidByWechatUserId($id)
    {
        return User::getDB()->where('uid', $id)->value('wechat_user_id');
    }

    /**
     * @param $account
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/22
     */
    public function accountByUser($account)
    {
        return User::getDB()->where('account', $account)->find();
    }

    /**
     * @param $uid
     * @return array
     * @author xaboy
     * @day 2020/6/22
     */
    public function getSubIds($uid)
    {
        return User::getDB()->where('spread_uid', $uid)->column('uid');
    }

    /**
     * @param $uid
     * @return int
     * @author xaboy
     * @day 2020/6/22
     */
    public function getOneLevelCount($uid)
    {
        return User::getDB()->where('spread_uid', $uid)->count();
    }

    /**
     * @param $uid
     * @return int
     * @author xaboy
     * @day 2020/6/22
     */
    public function getTwoLevelCount($uid)
    {
        $ids = $this->getSubIds($uid);
        return count($ids) ? User::getDB()->whereIn('spread_uid', $ids)->count() : 0;
    }

    /**
     * @param $ids
     * @return array
     * @author xaboy
     * @day 2020/6/22
     */
    public function getSubAllIds(array $ids)
    {
        return User::getDB()->whereIn('spread_uid', $ids)->column('uid');
    }

    /**
     * @param array $ids
     * @param string $field
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/22
     */
    public function users(array $ids, $field = '*')
    {
        return User::getDB()->whereIn('uid', $ids)->field($field)->select();
    }

    public function newUserNum($date)
    {
        return User::getDB()->when($date, function ($query, $date) {
            getModelTime($query, $date, 'create_time');
        })->count();
    }

    public function userOrderDetail($uid)
    {
        return User::getDB()->alias('A')->with(['group','spread'])
            ->join('StoreOrder B', 'A.uid = B.uid and B.paid = 1 and B.pay_time between \'' . date('Y/m/d', strtotime('first day of')) . ' 00:00:00\' and \'' . date('Y/m/d H:i:s') . '\'')
            ->join('PresellOrder C', 'C.order_id = B.order_id and C.paid = 1', 'LEFT')
            ->field('A.*, sum(B.pay_price + IFNULL(C.pay_price,0)) as total_pay_price, count(B.order_id) as total_pay_count,is_svip,svip_endtime,svip_save_money')
            ->where('A.uid', $uid)
            ->find()->append(['userLabel']);
    }

    public function userNumGroup($date)
    {
        return User::getDB()->when($date, function ($query, $date) {
            getModelTime($query, $date, 'create_time');
        })->field(Db::raw('from_unixtime(unix_timestamp(create_time),\'%m-%d\') as time, count(uid) as new'))
            ->group('time')->order('time ASC')->select();
    }

    public function idsByPayCount(array $ids)
    {
        return User::getDB()->whereIn('uid', $ids)->column('pay_count', 'uid');
    }

    public function beforeUserNum($date)
    {
        return User::getDB()->where('create_time', '<', $date)->count();
    }

    public function selfUserList($phone)
    {
        return User::getDB()->where('phone', $phone)->field('uid,nickname,avatar,user_type')->select();
    }

    public function initSpreadLimitDay(int $day)
    {
        return User::getDB()->where('spread_uid', '>', 0)->update(['spread_limit' => date('Y-m-d H:i:s', strtotime("+ $day day"))]);
    }

    public function clearSpreadLimitDay()
    {
        return User::getDB()->where('spread_uid', '>', 0)->update(['spread_limit' => null]);
    }

    public function updateSpreadLimitDay(int $day)
    {
        User::getDB()->where('spread_uid', '>', 0)->whereNull('spread_limit')->update(['spread_limit' => date('Y-m-d H:i:s', strtotime("+ $day day"))]);
        return User::getDB()->where('spread_uid', '>', 0)->whereNotNull('spread_limit')->update(['spread_limit' => Db::raw('TIMESTAMPADD(DAY, ' . $day . ', `spread_limit`)')]);
    }

    public function syncSpreadStatus()
    {
        return User::getDB()->where('spread_uid', '>', 0)->whereNotNull('spread_limit')->where('spread_limit', '<=', date('Y-m-d H:i:s'))->update(['spread_time' => null, 'spread_uid' => 0, 'spread_limit' => null]);
    }

    public function incSpreadCount($uid)
    {
        User::getDB()->where('uid', $uid)->update([
            'spread_count' => Db::raw('spread_count + 1')
        ]);
    }

    public function decSpreadCount($uid)
    {
        User::getDB()->where('uid', $uid)->where('spread_count','>',0)->update([
            'spread_count' => Db::raw('spread_count - 1')
        ]);
    }
}
