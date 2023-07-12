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


use app\common\dao\store\order\StoreOrderProfitsharingDao;
use app\common\model\store\order\StoreOrder;
use app\common\model\store\order\StoreOrderProfitsharing;
use app\common\model\store\order\StoreRefundOrder;
use app\common\repositories\BaseRepository;
use crmeb\services\WechatService;
use think\exception\ValidateException;

/**
 * @mixin StoreOrderProfitsharingDao
 */
class StoreOrderProfitsharingRepository extends BaseRepository
{

    const PROFITSHARING_TYPE_ORDER = 'order';
    const PROFITSHARING_TYPE_PRESELL = 'presell';
    /**
     * @var StoreOrderProfitsharingDao
     */
    protected $dao;

    public function __construct(StoreOrderProfitsharingDao $storeOrderProfitsharingDao)
    {
        $this->dao = $storeOrderProfitsharingDao;
    }

    public function getList(array $where, $page, $limit, $merchant = false)
    {
        $query = $this->dao->search($where)->with(['order' => function ($query) {
            $query->field('order_id,order_sn');
        }])->order('create_time DESC');
        $count = $query->count();
        $append = ['statusName', 'typeName'];
        if (!$merchant) {
            $append[] = 'merchant';
        }
        $list = $query->page($page, $limit)->append($append)->select();
        return compact('list', 'count');
    }

    public function refundPrice(StoreRefundOrder $refundOrder, $price, $refundMerPrice)
    {
        $this->refundPresallPrice($refundOrder, $price, $refundMerPrice, true);
    }

    public function refundPresallPrice(StoreRefundOrder $refundOrder, $price, $refundMerPrice, $order = false)
    {
        $model = $order ? $refundOrder->order->firstProfitsharing : $refundOrder->order->presellProfitsharing;
        if (!$model)
            throw new ValidateException('分账订单不存在');
        $model->profitsharing_refund = bcadd($model->profitsharing_refund, $price, 2);
        $model->profitsharing_mer_price = bcsub($model->profitsharing_mer_price, $refundMerPrice, 2);
        if ($model->profitsharing_refund >= $model->profitsharing_price) {
            $model->status = -1;
        }
        $model->save();
    }

    public function profitsharingOrder(StoreOrder $storeOrder)
    {
        foreach ($storeOrder->profitsharing as $profitsharing) {
            $this->profitsharing($profitsharing);
        }
    }

    public function profitsharing(StoreOrderProfitsharing $profitsharing)
    {
        $status = 1;
        $error_msg = '';
        $flag = true;
        try {
            if (bcsub($profitsharing->profitsharing_price, $profitsharing->profitsharing_mer_price, 2) > 0) {
                WechatService::create()->combinePay()->profitsharingOrder($profitsharing->getProfitsharingParmas(), true);
            } else {
                WechatService::create()->combinePay()->profitsharingFinishOrder($profitsharing->getProfitsharingFinishParmas());
            }
            $profitsharing->profitsharing_time = date('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $status = -2;
            $error_msg = $e->getMessage();
            $flag = false;
        }
        $profitsharing->status = $status;
        $profitsharing->error_msg = $error_msg;
        $profitsharing->save();
        return $flag;
    }

}
