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


namespace app\common\repositories\store\order;

use app\common\dao\store\order\StoreOrderStatusDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\service\StoreServiceRepository;
use app\common\repositories\store\service\StoreServiceUserRepository;

/**
 * Class StoreOrderStatusRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/6/11
 * @mixin StoreOrderStatusDao
 */
class StoreOrderStatusRepository extends BaseRepository
{
    //订单日志
    public const TYPE_ORDER  = 'order';
    //退款单日志
    public const TYPE_REFUND = 'refund';
    //商品日志
//    public const TYPE_PRODUCT = 'product';

    //操作者类型
    public const U_TYPE_SYSTEM = 0;
    public const U_TYPE_USER = 1;
    public const U_TYPE_ADMIN = 2;
    public const U_TYPE_MERCHANT = 3;
    public const U_TYPE_SERVICE = 4;

    //订单变动类型
    //取消
    const ORDER_STATUS_CANCEL = 'cancel';
    //改价
    const ORDER_STATUS_CHANGE = 'change';
    //创建
    const ORDER_STATUS_CREATE = 'create';
    //删除
    const ORDER_STATUS_DELETE = 'delete';
    //收货
    const ORDER_STATUS_TAKE   = 'take';
    //拆单
    const ORDER_STATUS_SPLIT   = 'split';
    //完成
    const ORDER_STATUS_OVER   = 'over';
    const ORDER_STATUS_AUTO_OVER    = 'auto_over';
    //预售订单
    const ORDER_STATUS_PRESELL= 'presell';
    const ORDER_STATUS_PRESELL_CLOSE = 'presell_close';
    //全部退款
    const ORDER_STATUS_REFUND_ALL   = 'refund_all';
    //支付成功
    const ORDER_STATUS_PAY_SUCCCESS  = 'pay_success';
    //拼图成功
    const ORDER_STATUS_GROUP_SUCCESS = 'group_success';
    //申请退款
    const CHANGE_REFUND_CREATGE = 'refund_create';
    //已发货
    const CHANGE_BACK_GOODS = 'back_goods';
    //退款申请已通过
    const CHANGE_REFUND_AGREE = 'refund_agree';
    //退款成功
    const CHANGE_REFUND_PRICE = 'refund_price';
    //订单退款已拒绝
    const CHANGE_REFUND_REFUSE = 'refund_refuse';
    //用户取消退款
    const CHANGE_REFUND_CANCEL = 'refund_cancel';

    /*
      2   => '待取货',
      3   => '配送中',
      4   => '已完成',
      -1  => '已取消',
      9   => '物品返回中',
      10  => '物品返回完成',
      100 => '骑士到店',
    */
    const ORDER_DELIVERY_COURIER    = 'delivery_0';
    const ORDER_DELIVERY_SELF       = 'delivery_1';
    const ORDER_DELIVERY_NOTHING    = 'delivery_2';
    const ORDER_DELIVERY_CITY       = 'delivery_5';
    const ORDER_DELIVERY_CITY_CANCEL  = 'delivery_5_-1';
    const ORDER_DELIVERY_CITY_ARRIVE  = 'delivery_5_100';
    const ORDER_DELIVERY_CITY_WAITING = 'delivery_5_2';
    const ORDER_DELIVERY_CITY_ING    = 'delivery_5_3';
    const ORDER_DELIVERY_CITY_OVER   = 'delivery_5_4';
    const ORDER_DELIVERY_CITY_REFUND = 'delivery_5_10';
    const ORDER_DELIVERY_CITY_REFUNDING = 'delivery_5_9';


    /**
     * StoreOrderStatusRepository constructor.
     * @param StoreOrderStatusDao $dao
     */
    public function __construct(StoreOrderStatusDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $order_id
     * @param $change_type
     * @param $change_message
     * @return \app\common\dao\BaseDao|\think\Model
     * @author xaboy
     * @day 2020/6/11
     */
    public function status($order_id, $change_type, $change_message)
    {
        return $this->dao->create(compact('order_id', 'change_message', 'change_type'));
    }

    public function search($where,$page, $limit)
    {
        $query = $this->dao->search($where)->order('change_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count','list');
    }

    public function createAdminLog(array $data)
    {
        try{
            $request = request();
            $data['user_type'] = $request->userType();
            $data['uid'] = $request->adminId();
            $data['nickname'] = $request->adminInfo()->real_name;
            return $this->dao->create($data);
        } catch (\Exception $e) {
            return $this->createSysLog($data);
        }
    }

    public function createServiceLog($service_id, array $data)
    {
        $service = app()->make(StoreServiceRepository::class)->getWhere(['service_id' => $service_id]);
        $data['user_type'] = self::U_TYPE_SERVICE;
        $data['uid'] = $service_id;
        $data['nickname'] = $service->nickname;
        return $this->dao->create($data);
    }

    public function createUserLog(array $data)
    {
        $data['user_type'] = self::U_TYPE_USER;
        try{
            $data['uid'] = request()->userInfo()->uid;
            $data['nickname'] = request()->userInfo()->nickname;
        } catch (\Exception $exception) {
            $data['uid'] = -1;
            $data['nickname'] = '';
        }
        return $this->dao->create($data);
    }

    public function createSysLog(array $data)
    {
        $data['user_type'] = self::U_TYPE_SYSTEM;
        $data['uid'] = 0;
        $data['nickname'] = '系统';
        return $this->dao->create($data);
    }

    public function batchCreateLog($data)
    {
        if(!empty($data)) {
            return $this->dao->insertAll($data);
        }
    }
}
