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
use app\common\model\user\UserLabel;
use app\common\model\user\UserMerchant;
use think\db\BaseQuery;

/**
 * Class UserMerchantDao
 * @package app\common\dao\user
 * @author xaboy
 * @day 2020/10/20
 */
class UserMerchantDao extends BaseDao
{

    /**
     * @return string
     * @author xaboy
     * @day 2020/10/20
     */
    protected function getModel(): string
    {
        return UserMerchant::class;
    }

    /**
     * @param $uid
     * @param $mer_id
     * @return bool
     * @author xaboy
     * @day 2020/10/20
     */
    public function isMerUser($uid, $mer_id)
    {
        return $this->existsWhere(compact('uid', 'mer_id'));
    }

    /**
     * @param $uid
     * @param $mer_id
     * @return int
     * @throws \think\db\exception\DbException
     * @author xaboy
     * @day 2020/10/20
     */
    public function updateLastTime($uid, $mer_id)
    {
        return UserMerchant::getDB()->where(compact('uid', 'mer_id'))->update([
            'last_time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * @param array $where
     * @return mixed
     * @author xaboy
     * @day 2020/10/20
     */
    public function search(array $where)
    {
        return UserMerchant::getDB()->alias('A')->leftJoin('User B', 'A.uid = B.uid')
            ->when(isset($where['mer_id']) && $where['mer_id'] !== '', function ($query) use ($where) {
                $query->where('A.mer_id', $where['mer_id']);
            })->when(isset($where['nickname']) && $where['nickname'], function (BaseQuery $query) use ($where) {
                return $query->where('B.nickname', 'like', '%' . $where['nickname'] . '%');
            })->when(isset($where['sex']) && $where['sex'] !== '', function (BaseQuery $query) use ($where) {
                return $query->where('B.sex', intval($where['sex']));
            })->when(isset($where['is_promoter']) && $where['is_promoter'] !== '', function (BaseQuery $query) use ($where) {
                return $query->where('B.is_promoter', $where['is_promoter']);
            })->when(isset($where['uids']), function (BaseQuery $query) use ($where) {
                return $query->whereIn('A.uid', $where['uids']);
            })->when(isset($where['user_time_type']) && $where['user_time_type'] !== '' && $where['user_time'] != '', function ($query) use ($where) {
                if ($where['user_time_type'] == 'visit') {
                    getModelTime($query, $where['user_time'], 'A.last_time');
                }
                if ($where['user_time_type'] == 'add_time') {
                    getModelTime($query, $where['user_time'], 'A.create_time');
                }
            })->when(isset($where['pay_count']) && $where['pay_count'] !== '', function ($query) use ($where) {
                if ($where['pay_count'] == -1) {
                    $query->where('A.pay_num', 0);
                } else {
                    $query->where('A.pay_num', '>', $where['pay_count']);
                }
            })->when(isset($where['label_id']) && $where['label_id'] !== '', function (BaseQuery $query) use ($where) {
                return $query->whereRaw('CONCAT(\',\',A.label_id,\',\') LIKE \'%,' . $where['label_id'] . ',%\'');
            })->when(isset($where['user_type']) && $where['user_type'] !== '', function (BaseQuery $query) use ($where) {
                return $query->where('B.user_type', $where['user_type']);
            })->where('A.status', 1);
    }

    public function numUserIds($mer_id, $min, $max = null)
    {
        return UserMerchant::getDB()->where('mer_id', $mer_id)->where('pay_num', '>=', $min)->when(!is_null($max), function ($query) use ($max) {
            $query->where('pay_num', '<=', $max);
        })->group('uid')->column('uid');
    }

    public function priceUserIds($mer_id, $min, $max = null)
    {
        return UserMerchant::getDB()->where('mer_id', $mer_id)->where('pay_price', '>=', $min)->when(!is_null($max), function ($query) use ($max, $min) {
            $query->where('pay_price', $min == $max ? '<=' : '<', $max);
        })->group('uid')->column('uid');
    }
}
