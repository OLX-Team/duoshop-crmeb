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


namespace app\common\repositories\store\coupon;


use app\common\dao\store\coupon\StoreCouponSendDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserMerchantRepository;
use app\common\repositories\user\UserRepository;
use crmeb\jobs\MerchantSendCouponJob;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;

/**
 * @mixin StoreCouponSendDao
 */
class StoreCouponSendRepository extends BaseRepository
{
    public function __construct(StoreCouponSendDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where)->with([
            'coupon' => function($query) {
                $query->field('coupon_id,type');
            }
        ]);
        $count = $query->count();
        $list = $query->setOption('field', [])->field('B.*,A.coupon_num,A.create_time,A.status as send_status, A.mark,A.coupon_send_id')
            ->append(['useCount', 'used_num'])->page($page, $limit)->order('coupon_send_id DESC')->select();
        return compact('count', 'list');
    }

    public function create($data, $merId)
    {
        $query = null;
        $coupon = app()->make(StoreCouponRepository::class)->getWhere(['mer_id' => $merId, 'coupon_id' => $data['coupon_id'], 'is_del' => 0]);
        if (!$coupon) {
            throw new ValidateException('优惠券不存在');
        }

        if ($merId){
            $userMerchantRepository = app()->make(UserMerchantRepository::class);
            $where = ['mer_id' => $merId];
            $field = 'A.uid';
        } else {
            $where = [];
            $userMerchantRepository = app()->make(UserRepository::class);
            $field = 'uid';
        }

        if ($data['is_all']) {
            $query = $userMerchantRepository->search($where + $data['search']);
        } else {
            $query = $userMerchantRepository->search($where + ['uids' => $data['uid']]);
        }

        $uid = $query->column($field);
        $uTotal = count($uid);
        if (!$uTotal) {
            throw new ValidateException('请选择用户');
        }
        if ($coupon['is_limited'] && $coupon->remain_count < $uTotal) {
            throw new ValidateException('该优惠券可领取数不足' . $uTotal);
        }

        return Db::transaction(function () use ($uid, $merId, $data, $coupon, $uTotal) {
            $search = $data['mark'];
            if($coupon['is_limited']){
                $coupon->remain_count -= $uTotal;
            }
            $send = $this->dao->create([
                'mer_id' => $merId,
                'coupon_id' => $coupon->coupon_id,
                'coupon_num' => $uTotal,
                'status' => 0,
                'mark' => [
                    'type' => $data['is_all'],
                    'search' => count($search) ? $search : null
                ]
            ]);
            $coupon->save();
            Cache::store('file')->set('_send_coupon' . $send->coupon_send_id, $uid);
            Queue::push(MerchantSendCouponJob::class, $send->coupon_send_id);
            return $send;
        });
    }

}
