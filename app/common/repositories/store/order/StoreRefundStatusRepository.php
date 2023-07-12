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
use app\common\dao\store\order\StoreRefundStatusDao;
use app\common\repositories\BaseRepository;

/**
 * Class StoreRefundStatusRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/6/12
 */
class StoreRefundStatusRepository extends BaseRepository
{
    //已发货
    const CHANGE_BACK_GOODS = 'back_goods';
    //创建退款单
    const CHANGE_CREATE = 'create';
    //删除记录
    const CHANGE_DELETE = 'delete';
    //退款申请已通过
    const CHANGE_REFUND_AGREE = 'refund_agree';
    //退款成功
    const CHANGE_REFUND_PRICE = 'refund_price';
    //订单退款已拒绝
    const CHANGE_REFUND_REFUSE = 'refund_refuse';
    //用户取消退款
    const CHANGE_REFUND_CANCEL = 'refund_cancel';
    /**
     * StoreRefundStatusRepository constructor.
     * @param StoreRefundStatusDao $dao
     */
    public function __construct(StoreRefundStatusDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $refund_order_id
     * @param $change_type
     * @param $change_message
     * @return \app\common\dao\BaseDao|\think\Model
     * @author xaboy
     * @day 2020/6/12
     */
    public function status($refund_order_id, $change_type, $change_message)
    {
        return $this->dao->create(compact('refund_order_id', 'change_message', 'change_type'));
    }

    public function search($id, $page, $limit)
    {
        $query = $this->dao->search($id);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }
}
