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


use app\common\dao\store\coupon\StoreCouponUserDao;
use app\common\repositories\BaseRepository;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class StoreCouponUserRepository
 * @package app\common\repositories\store\coupon
 * @author xaboy
 * @day 2020-05-14
 * @mixin  StoreCouponUserDao
 */
class StoreCouponUserRepository extends BaseRepository
{

    //获取方式(receive:自己领取 send:后台发送  give:满赠  new:新人 buy:买赠送)
    const SEND_TYPE_BUY = 'buy';
    const SEND_TYPE_RECEIVE = 'receive';
    const SEND_TYPE_SEND = 'send';
    const SEND_TYPE_GIVE = 'give';
    const SEND_TYPE_NEW = 'new';
    /**
     * @var StoreCouponUserDao
     */
    protected $dao;

    /**
     * StoreCouponUserRepository constructor.
     * @param StoreCouponUserDao $dao
     */
    public function __construct(StoreCouponUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/3
     */
    public function userList(array $where, $page, $limit)
    {
        if ($page == 1) {
            $this->dao->failCoupon();
        }
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->with(['coupon' => function ($query) {
            $query->field('coupon_id,type,send_type');
        }, 'merchant' => function ($query) {
            $query->field('mer_id,mer_name,mer_avatar');
        }])->page($page, $limit)->select();
        return compact('count', 'list');
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/3
     */
    public function getList(array $where, $page, $limit)
    {
        if ($page == 1) {
            $this->dao->failCoupon();
        }
        $query = $this->dao->search($where)->with([
            'user' => function ($query) {
                $query->field('avatar,uid,nickname,user_type');
            },
            'coupon' => function ($query) {
                 $query->field('coupon_id,type');
            },
            'merchant' => function ($query) {
                $query->field('mer_id,mer_name,mer_state,mer_avatar,is_trader,type_id');
            }
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

}
