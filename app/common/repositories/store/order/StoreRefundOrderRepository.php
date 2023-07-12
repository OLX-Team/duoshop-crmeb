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


use app\common\dao\store\order\StoreRefundOrderDao;
use app\common\model\store\order\StoreOrder;
use app\common\model\store\order\StoreRefundOrder;
use app\common\repositories\BaseRepository;
use app\common\repositories\store\product\ProductRepository;
use app\common\repositories\system\merchant\FinancialRecordRepository;
use app\common\repositories\system\merchant\MerchantRepository;
use app\common\repositories\user\UserBillRepository;
use app\common\repositories\user\UserRepository;
use crmeb\jobs\SendSmsJob;
use crmeb\services\AlipayService;
use crmeb\services\ExpressService;
use crmeb\services\MiniProgramService;
use crmeb\services\SwooleTaskService;
use crmeb\services\WechatService;
use Exception;
use FormBuilder\Factory\Elm;
use FormBuilder\Form;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Route;
use think\Model;

/**
 * Class StoreRefundOrderRepository
 * @package app\common\repositories\store\order
 * @author xaboy
 * @day 2020/6/12
 * @mixin StoreRefundOrderDao
 */
class StoreRefundOrderRepository extends BaseRepository
{

    //状态 0:待审核 -1:审核未通过 1:待退货 2:待收货 3:已退款 -10 取消
    public const REFUND_STATUS_WAIT = 0;
    public const REFUND_STATUS_BACK = 1;
    public const REFUND_STATUS_THEGOODS = 2;
    public const REFUND_STATUS_SUCCESS = 1;
    public const REFUND_STATUS_REFUSED = -1;
    public const REFUND_STATUS_CANCEL= -2;


    /**
     * StoreRefundOrderRepository constructor.
     * @param StoreRefundOrderDao $dao
     */
    public function __construct(StoreRefundOrderDao $dao)
    {
        $this->dao = $dao;
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
     * @day 2020/6/12
     */
    public function userList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->setOption('field', [])->field('refund_order_id,refund_price,mer_id,status')
            ->with(['merchant' => function ($query) {
                $query->field('mer_name,mer_id');
            }, 'refundProduct.product'])->page($page, $limit)->select();
        return compact('list', 'count');
    }

    /**
     * @param $id
     * @param $uid
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/12
     */
    public function userDetail($id, $uid)
    {
        return $this->dao->search([
            'id' => $id,
            'uid' => $uid,
            'is_del' => 0,
        ])->with('refundProduct.product')->append(['auto_refund_time'])->find();
    }

    /**
     * @param $id
     * @param $uid
     * @author xaboy
     * @day 2020/6/17
     */
    public function userDel($id, $uid)
    {
        $ret = $this->dao->get($id);
        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $ret->refund_order_id,
            'order_sn' => $ret->refund_order_sn,
            'type' => $storeOrderStatusRepository::TYPE_REFUND,
            'change_message' => '创建退款单',
            'change_type' => $storeOrderStatusRepository::ORDER_STATUS_DELETE,
        ];
        Db::transaction(function () use ($uid, $id,$storeOrderStatusRepository,$orderStatus) {
            $this->dao->userDel($uid, $id);
            $storeOrderStatusRepository->createUserLog($orderStatus);
        });
    }

    public function createRefund(StoreOrder $order, $refund_type = 1, $refund_message = '自动发起退款', $refund_postage = true)
    {
        $products = $order->orderProduct;
        $ids = array_column($products->toArray(), 'order_product_id');
        $productRefundPrices = app()->make(StoreRefundProductRepository::class)->userRefundPrice($ids);

        $totalRefundPrice = 0;
        $totalRefundNum = 0;
        $total_extension_one = 0;
        $total_extension_two = 0;
        $totalIntegral = 0;
        $totalPlatformRefundPrice = 0;
        $totalPostage = 0;
        $refundProduct = [];
        $refund_order_id = 0;
        foreach ($products as $product) {
            $productRefundPrice = $productRefundPrices[$product['order_product_id']] ?? [];
            if ($product['extension_one'] > 0)
                $total_extension_one = bcadd($total_extension_one, bcmul($product['refund_num'], $product['extension_one'], 2), 2);
            if ($product['extension_two'] > 0)
                $total_extension_two = bcadd($total_extension_two, bcmul($product['refund_num'], $product['extension_two'], 2), 2);
            $postagePrice = ($refund_postage || !$order->status || $order->status == 9) ? bcsub($product['postage_price'], $productRefundPrice['refund_postage'] ?? 0, 2) : 0;
            $totalRefundNum += $product['refund_num'];
            $refundPrice = 0;
            //计算可退金额
            if ($product['product_price'] > 0) {
                $refundPrice = bcsub($product['product_price'], bcsub($productRefundPrice['refund_price'] ?? 0, $productRefundPrice['refund_postage'] ?? 0, 2), 2);
            }
            $platform_refund_price = 0;
            //计算退的平台优惠券金额
            if ($product['platform_coupon_price'] > 0) {
                $platform_refund_price = bcsub($product['platform_coupon_price'], $productRefundPrice['platform_refund_price'] ?? 0, 2);
            }
            $integral = 0;
            if ($product['integral'] > 0) {
                $integral = bcsub($product['integral_total'], $productRefundPrice['refund_integral'] ?? 0, 0);
            }

            $totalPostage = bcadd($totalPostage, $postagePrice, 2);
            $totalRefundPrice = bcadd($totalRefundPrice, $refundPrice, 2);
            $totalPlatformRefundPrice = bcadd($totalPlatformRefundPrice, $platform_refund_price, 2);
            $totalIntegral = bcadd($totalIntegral, $integral, 2);

            $refundProduct[] = [
                'refund_order_id' => &$refund_order_id,
                'refund_num' => $product['refund_num'],
                'order_product_id' => $product['order_product_id'],
                'platform_refund_price' => $platform_refund_price,
                'refund_integral' => $integral,
                'refund_price' => $refundPrice,
                'refund_postage' => $postagePrice,
            ];
        }
        $data = compact('refund_message', 'refund_type');
        $data['order_id'] = $products[0]['order_id'];
        $data['uid'] = $products[0]['uid'];
        $data['mer_id'] = $order['mer_id'];
        $data['refund_order_sn'] = app()->make(StoreOrderRepository::class)->getNewOrderId(StoreOrderRepository::TYPE_SN_REFUND);
        $data['refund_num'] = $totalRefundNum;
        $data['extension_one'] = $total_extension_one;
        $data['extension_two'] = $total_extension_two;
        $data['refund_price'] = bcadd($totalPostage, $totalRefundPrice, 2);
        $data['integral'] = $totalIntegral;
        $data['platform_refund_price'] = $totalPlatformRefundPrice;
        $data['refund_postage'] = $totalPostage;
        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);

        return Db::transaction(function () use ($refundProduct, $data, $products, $order, &$refund_order_id,$storeOrderStatusRepository,$refund_message) {
            event('refund.creates.before', compact('data'));
            $refund = $this->dao->create($data);
            $refund_order_id = $refund->refund_order_id;
            foreach ($products as $product) {
                $product->refund_num = 0;
                $product->is_refund = 1;
                $product->save();
            }
            $orderStatus = [
                'order_id' => $refund->refund_order_id,
                'order_sn' => $order->refund_order_sn,
                'type' => $storeOrderStatusRepository::TYPE_REFUND,
                'change_message' => $refund_message,
                'change_type' => $storeOrderStatusRepository::ORDER_STATUS_CREATE,
            ];
            $storeOrderStatusRepository->createSysLog($orderStatus);
            app()->make(StoreRefundProductRepository::class)->insertAll($refundProduct);
            return $refund;
        });
    }

    public function getRefundsTotalPrice($order, $products)
    {
        $productRefundPrices = app()->make(StoreRefundProductRepository::class)->userRefundPrice($products->column('order_product_id'));
        $totalPostage = 0;
        $totalRefundPrice = 0;
        foreach ($products as $product) {
            if (!$product['refund_switch']) throw new ValidateException('部分商品不支持退款');
            $productRefundPrice = $productRefundPrices[$product['order_product_id']] ?? [];
            $postagePrice = (!$order->status || $order->status == 9) ? bcsub($product['postage_price'], $productRefundPrice['refund_postage'] ?? 0, 2) : 0;
            $refundPrice = 0;
            if ($product['product_price'] > 0) {
                $refundPrice = bcsub($product['product_price'], bcsub($productRefundPrice['refund_price'] ?? 0,$productRefundPrice['refund_postage']??0 ,2), 2);
            }
            $totalPostage = bcadd($totalPostage, $postagePrice, 2);
            $totalRefundPrice = bcadd($totalRefundPrice, $refundPrice, 2);
        }
        return bcadd($totalPostage, $totalRefundPrice, 2);
    }

    public function getRefundTotalPrice($order, $products)
    {
        $productRefundPrices = app()->make(StoreRefundProductRepository::class)->userRefundPrice($products->column('order_product_id'));
        $product = $products[0];
        if (!$product['refund_switch']) throw new ValidateException('商品不支持退款');
        $productRefundPrice = $productRefundPrices[$product['order_product_id']] ?? [];
        $total_refund_price = bcsub($product['product_price'], bcsub($productRefundPrice['refund_price'] ?? 0, $productRefundPrice['refund_postage'] ?? 0, 2), 2);
        $postage_price = (!$order->status || $order->status == 9) ? bcsub($product['postage_price'], $productRefundPrice['refund_postage'] ?? 0, 2) : 0;

        return compact('total_refund_price', 'postage_price');
    }

    /**
     * @param StoreOrder $order
     * @param array $ids
     * @param $uid
     * @param array $data
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/17
     */
    public function refunds(StoreOrder $order, array $ids, $uid, array $data)
    {
        $orderId = $order->order_id;
        $products = app()->make(StoreOrderProductRepository::class)->userRefundProducts($ids, $uid, $orderId);
        if (!$products || count($ids) != count($products))
            throw new ValidateException('请选择正确的退款商品');
        $productRefundPrices = app()->make(StoreRefundProductRepository::class)->userRefundPrice($ids);
        $totalRefundPrice = 0;
        $totalRefundNum = 0;
        $total_extension_one = 0;
        $total_extension_two = 0;
        $totalIntegral = 0;
        $totalPlatformRefundPrice = 0;
        $totalPostage = 0;
        $refundProduct = [];
        $refund_order_id = 0;
        foreach ($products as $product) {
            $productRefundPrice = $productRefundPrices[$product['order_product_id']] ?? [];
            if ($product['extension_one'] > 0)
                $total_extension_one = bcadd($total_extension_one, bcmul($product['refund_num'], $product['extension_one'], 2), 2);
            if ($product['extension_two'] > 0)
                $total_extension_two = bcadd($total_extension_two, bcmul($product['refund_num'], $product['extension_two'], 2), 2);
            $postagePrice = (!$order->status || $order->status == 9) ? bcsub($product['postage_price'], $productRefundPrice['refund_postage'] ?? 0, 2) : 0;
            $totalRefundNum += $product['refund_num'];
            $refundPrice = 0;
            //计算可退金额
            if ($product['product_price'] > 0) {
                $refundPrice = bcsub($product['product_price'], bcsub($productRefundPrice['refund_price'] ?? 0, $productRefundPrice['refund_postage'] ?? 0, 2), 2);
            }
            $platform_refund_price = 0;
            //计算退的平台优惠券金额
            if ($product['platform_coupon_price'] > 0) {
                $platform_refund_price = bcsub($product['platform_coupon_price'], $productRefundPrice['platform_refund_price'] ?? 0, 2);
            }
            $integral = 0;
            if ($product['integral'] > 0) {
                $integral = bcsub($product['integral_total'], $productRefundPrice['refund_integral'] ?? 0, 0);
            }

            $totalPostage = bcadd($totalPostage, $postagePrice, 2);
            $totalRefundPrice = bcadd($totalRefundPrice, $refundPrice, 2);
            $totalPlatformRefundPrice = bcadd($totalPlatformRefundPrice, $platform_refund_price, 2);
            $totalIntegral = bcadd($totalIntegral, $integral, 2);

            $refundProduct[] = [
                'refund_order_id' => &$refund_order_id,
                'refund_num' => $product['refund_num'],
                'order_product_id' => $product['order_product_id'],
                'platform_refund_price' => $platform_refund_price,
                'refund_integral' => $integral,
                'refund_price' => $refundPrice,
                'refund_postage' => $postagePrice,
            ];
        }
        $data['order_id'] = $products[0]['order_id'];
        $data['uid'] = $products[0]['uid'];
        $data['mer_id'] = $order['mer_id'];
        $data['refund_order_sn'] = app()->make(StoreOrderRepository::class)->getNewOrderId(StoreOrderRepository::TYPE_SN_REFUND);
        $data['refund_num'] = $totalRefundNum;
        $data['extension_one'] = $total_extension_one;
        $data['extension_two'] = $total_extension_two;
        $data['refund_price'] = bcadd($totalPostage, $totalRefundPrice, 2);
        $data['integral'] = $totalIntegral;
        $data['platform_refund_price'] = $totalPlatformRefundPrice;
        $data['refund_postage'] = $totalPostage;

        return Db::transaction(function () use ($refundProduct, $data, $products, $order, &$refund_order_id) {
            event('refund.creates.before', compact('data'));
            $refund = $this->dao->create($data);
            $refund_order_id = $refund->refund_order_id;
            foreach ($products as $product) {
                $product->refund_num = 0;
                $product->is_refund = 1;
                $product->save();
            }
            app()->make(StoreRefundProductRepository::class)->insertAll($refundProduct);
            $this->applyRefundAfter($refund, $order);
            return $refund;
        });
    }

    public function applyRefundAfter($refund, $order)
    {
        event('refund.create', compact('refund', 'order'));
        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $refund->refund_order_id,
            'order_sn' => $refund->refund_order_sn,
            'type' => $storeOrderStatusRepository::TYPE_REFUND,
            'change_message' => '创建退款单',
            'change_type' => $storeOrderStatusRepository::ORDER_STATUS_CREATE,
        ];
        $storeOrderStatusRepository->createUserLog($orderStatus);
        $orderStatus = [
            'order_id' => $order->order_id,
            'order_sn' => $order->order_sn,
            'type' => $storeOrderStatusRepository::TYPE_ORDER,
            'change_message' => '申请退款',
            'change_type' => $storeOrderStatusRepository::CHANGE_REFUND_CREATGE,
        ];
        $storeOrderStatusRepository->createUserLog($orderStatus);
        Queue::push(SendSmsJob::class, ['tempId' => 'ADMIN_RETURN_GOODS_CODE', 'id' => $refund->refund_order_id]);
        SwooleTaskService::merchant('notice', [
            'type' => 'new_refund_order',
            'data' => [
                'title' => '新退款单',
                'message' => '您有一个新的退款单',
                'id' => $refund->refund_order_id
            ]
        ], $order->mer_id);
    }

    /**
     * @param StoreOrder $order
     * @param $productId
     * @param $num
     * @param $uid
     * @param array $data
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/17
     */
    public function refund(StoreOrder $order, $productId, $num, $uid, array $data)
    {
        $orderId = $order->order_id;
        //TODO 订单状态生成佣金
        $product = app()->make(StoreOrderProductRepository::class)->userRefundProducts([$productId], $uid, $orderId);
        if (!$product)
            throw new ValidateException('请选择正确的退款商品');
        $product = $product[0];
        if ($product['refund_num'] < $num)
            throw new ValidateException('可退款商品不足' . floatval($num) . '件');
        $productRefundPrice = app()->make(StoreRefundProductRepository::class)->userRefundPrice([$productId])[$productId] ?? [];

        //计算可退运费
        $postagePrice = (!$order->status || $order->status == 9) ? bcsub($product['postage_price'], $productRefundPrice['refund_postage'] ?? 0, 2) : 0;

        $refundPrice = 0;
        //计算可退金额
        if ($product['product_price'] > 0) {
            if ($product['refund_num'] == $num) {
                $refundPrice = bcsub($product['product_price'], bcsub($productRefundPrice['refund_price'] ?? 0, $productRefundPrice['refund_postage'] ?? 0, 2), 2);
            } else {
                $refundPrice = bcmul(bcdiv($product['product_price'], $product['product_num'], 2), $num, 2);
            }
        }
        $totalRefundPrice = bcadd($refundPrice, $postagePrice, 2);
        if ($totalRefundPrice < $data['refund_price'])
            throw new ValidateException('最高可退款' . floatval($totalRefundPrice) . '元');

        $data['refund_postage'] = 0;

        if ($data['refund_price'] > $refundPrice) {
            $data['refund_postage'] = bcsub($data['refund_price'], $refundPrice, 2);
        }

        $data['order_id'] = $product['order_id'];

        $platform_refund_price = 0;
        //计算退的平台优惠券金额
        if ($product['platform_coupon_price'] > 0) {
            if ($product['refund_num'] == $num) {
                $platform_refund_price = bcsub($product['platform_coupon_price'], $productRefundPrice['platform_refund_price'] ?? 0, 2);
            } else {
                $platform_refund_price = bcmul(bcdiv($product['platform_coupon_price'], $product['product_num'], 2), $num, 2);
            }
        }

        $data['platform_refund_price'] = $platform_refund_price;

        $integral = 0;
        if ($product['integral'] > 0) {
            if ($product['refund_num'] == $num) {
                $integral = bcsub($product['integral_total'], $productRefundPrice['refund_integral'] ?? 0, 0);
            } else {
                $integral = bcmul($product['integral'], $num, 0);
            }
        }

        $data['integral'] = $integral;

        $total_extension_one = 0;
        $total_extension_two = 0;
        if ($product['extension_one'] > 0)
            $total_extension_one = bcmul($num, $product['extension_one'], 2);
        if ($product['extension_two'] > 0)
            $total_extension_two = bcmul($num, $product['extension_two'], 2);

        $data['uid'] = $product['uid'];
        $data['mer_id'] = $order['mer_id'];
        $data['refund_order_sn'] = app()->make(StoreOrderRepository::class)->getNewOrderId(StoreOrderRepository::TYPE_SN_REFUND);
        $data['refund_num'] = $num;
        $data['extension_one'] = $total_extension_one;
        $data['extension_two'] = $total_extension_two;

        return Db::transaction(function () use ($order, $data, $product, $productId, $num) {
            event('refund.create.before', compact('data'));
            $refund = $this->dao->create($data);
            app()->make(StoreRefundProductRepository::class)->create([
                'refund_num' => $num,
                'refund_order_id' => $refund->refund_order_id,
                'order_product_id' => $productId,
                'platform_refund_price' => $data['platform_refund_price'],
                'refund_price' => $data['refund_price'],
                'refund_integral' => $data['integral'],
                'refund_postage' => $data['refund_postage'],
            ]);
            $product->refund_num -= $num;
            $product->is_refund = 1;
            $product->save();
            $this->applyRefundAfter($refund, $order);
            return $refund;
        });
    }



    /**
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-06-12
     */
    public function getList(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where)->where('is_system_del', 0)->where('status','<>',-2)->with([
            'order' => function ($query) {
                $query->field('order_id,order_sn,activity_type,real_name,user_address,status,order_type,is_del');
            },
            'refundProduct.product',
            'user' => function ($query) {
                $query->field('uid,nickname,phone');
            }])
            ->order('create_time DESC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        $stat = [
            'count' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id']]),
            'audit' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 0]),
            'refuse' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => -1]),
            'agree' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 1]),
            'backgood' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 2]),
            'end' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 3]),
        ];
        return compact('count', 'list', 'stat');
    }

    public function getListByService(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where)->where('is_system_del', 0)
            ->with([
                'order' => function ($query) {
                    $query->field('order_id,order_sn,activity_type,real_name,user_address');
                },
                'refundProduct.product',
//                'user' => function ($query) {
//                    $query->field('uid,nickname,phone');
//                }
             ])
            ->order('create_time DESC,status ASC');
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        return compact('count', 'list');
    }

    public function getAdminList(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where)->where('status','<>',-2)->with(['order' => function ($query) {
            $query->field('order_id,order_sn,activity_type');
        }, 'refundProduct.product', 'user' => function ($query) {
            $query->field('uid,nickname,phone');
        }]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        $stat = [
            'count' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id']]),
            'audit' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 0]),
            'refuse' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => -1]),
            'agree' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 1]),
            'backgood' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 2]),
            'end' => $this->dao->getWhereCount(['is_system_del' => 0, 'mer_id' => $where['mer_id'], 'status' => 3]),
        ];
        return compact('count', 'list', 'stat');
    }


    /**
     * TODO 总后台所有订单
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @author Qinii
     * @day 2020-06-25
     */
    public function getAllList(array $where, int $page, int $limit)
    {
        $query = $this->dao->search($where)->with(['order' => function ($query) {
            $query->field('order_id,order_sn,activity_type');
        }, 'merchant' => function ($query) {
            $query->field('mer_id,mer_name,is_trader');
        }, 'refundProduct.product', 'user' => function ($query) {
            $query->field('uid,nickname,phone');
        }]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();
        $stat = [
            'all' => $this->dao->getWhereCount([]),
            'audit' => $this->dao->getWhereCount(['status' => 0]),
            'refuse' => $this->dao->getWhereCount(['status' => -1]),
            'agree' => $this->dao->getWhereCount(['status' => 1]),
            'backgood' => $this->dao->getWhereCount(['status' => 2]),
            'end' => $this->dao->getWhereCount(['status' => 3]),
        ];
        return compact('count', 'list', 'stat');
    }

    public function reconList($where, $page, $limit)
    {
        $ids = app()->make(MerchantReconciliationOrderRepository::class)->getIds($where);
        $query = $this->dao->search([])->whereIn('refund_order_id', $ids)->with(['order' => function ($query) {
            $query->field('order_id,order_sn,activity_type');
        }, 'refundProduct.product', 'user' => function ($query) {
            $query->field('uid,nickname,phone');
        }]);
        $count = $query->count();
        $list = $query->page($page, $limit)->select();

        return compact('count', 'list');
    }

    /**
     * @param int $merId
     * @param int $id
     * @return bool
     * @author Qinii
     * @day 2020-06-12
     */
    public function getStatusExists(int $merId, int $id)
    {
        $where = [
            'mer_id' => $merId,
            'refund_order_id' => $id,
            'status' => 0,
        ];
        return $this->dao->getFieldExists($where);
    }

    /**
     * TODO 是否为收到退货状态
     * @param int $merId
     * @param int $id
     * @return bool
     * @author Qinii
     * @day 2020-06-13
     */
    public function getRefundPriceExists(int $merId, int $id)
    {
        $where = [
            'mer_id' => $merId,
            'refund_order_id' => $id,
            'status' => 2,
        ];
        return $this->dao->getFieldExists($where);
    }

    public function getUserDelExists(int $merId, int $id)
    {
        $where = [
            'mer_id' => $merId,
            'refund_order_id' => $id,
            'is_del' => 1,
        ];
        return $this->dao->getFieldExists($where);
    }

    /**
     * @param int $merId
     * @param int $id
     * @return bool
     * @author Qinii
     * @day 2020-06-12
     */
    public function getExistsById(int $merId, int $id)
    {
        $where = [
            'mer_id' => $merId,
            'refund_order_id' => $id,
        ];
        return $this->dao->getFieldExists($where);
    }

    public function markForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('merchantStoreRefundMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('mer_mark', '备注', $data['mer_mark'])
        ]);

        return $form->setTitle('备注信息');
    }

    public function adminMarkForm($id)
    {
        $data = $this->dao->get($id);
        $form = Elm::createForm(Route::buildUrl('systemMerchantRefundOrderMark', ['id' => $id])->build());
        $form->setRule([
            Elm::text('admin_mark', '备注', $data['admin_mark'])
        ]);

        return $form->setTitle('备注信息');
    }

    /**
     * TODO 退款单已发货
     * @param $id
     * @return Form
     * @author Qinii
     * @day 2020-06-13
     */

    public function backGoods($uid, $id, $data)
    {
        $refund = $this->userDetail($id, $uid);
        if (!$refund)
            throw new ValidateException('退款单不存在');
        if ($refund->status != 1)
            throw new ValidateException('退款单状态有误');
        $refund->status = 2;
        $refund->status_time = date('Y-m-d H:i:s');

        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $refund->refund_order_id,
            'order_sn' => $refund->refund_order_sn,
            'type' => $storeOrderStatusRepository::TYPE_REFUND,
            'change_message' => '退款单退回商品已发货',
            'change_type' => $storeOrderStatusRepository::CHANGE_BACK_GOODS,
        ];
        Db::transaction(function () use ($refund, $data, $id, $uid,$storeOrderStatusRepository,$orderStatus) {
            $refund->save($data);
            $storeOrderStatusRepository->createUserLog($orderStatus);
            event('refund.backGoods',compact('uid','id','data'));
        });
        Queue::push(SendSmsJob::class, [
            'tempId' => 'ADMIN_DELIVERY_CODE',
            'id' => $id
        ]);
    }

    /**
     * TODO
     * @param $id
     * @return Form
     * @author Qinii
     * @day 2020-06-13
     */
    public function statusForm($id)
    {
        $res = $this->getWhere(['refund_order_id' => $id]);
        $form = Elm::createForm(Route::buildUrl('merchantStoreRefundOrderSwitchStatus', ['id' => $id])->build());

        if ($res['refund_type'] == 1) {
            $form->setRule([
                Elm::radio('status', '审核', -1)->setOptions([
                    ['value' => 1, 'label' => '同意'],
                    ['value' => -1, 'label' => '拒绝'],
                ])->control([
                    [
                        'value' => -1,
                        'rule' => [
                            Elm::input('fail_message', '拒绝原因')->required()
                        ]
                    ],
                ]),
            ]);
        }
        if ($res['refund_type'] == 2) {
            $form->setRule([
                Elm::radio('status', '审核', -1)->setOptions([
                    ['value' => 1, 'label' => '同意'],
                    ['value' => -1, 'label' => '拒绝'],
                ])->control([
                    [
                        'value' => 1,
                        'rule' => [
                            Elm::input('mer_delivery_user', '收货人', merchantConfig($res['mer_id'], 'mer_refund_user'))->required(),
                            Elm::input('mer_delivery_address', '收货地址', merchantConfig($res['mer_id'], 'mer_refund_address'))->required(),
                            Elm::input('phone', '手机号', merchantConfig($res['mer_id'], 'set_phone'))->required(),
                        ]
                    ],
                    [
                        'value' => -1,
                        'rule' => [
                            Elm::input('fail_message', '拒绝原因')->required()
                        ]
                    ],
                ]),

            ]);
        }

        return $form->setTitle('退款审核');
    }

    /**
     * TODO 拒绝退款
     * @param $id
     * @param $data
     * @author Qinii
     * @day 2020-06-13
     */
    public function refuse($id, $data, $service_id = 0)
    {
        $refund = $this->getWhere(['refund_order_id' => $id], '*', ['refundProduct.product']);
        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $refund->order_id,
            'order_sn' => $refund->order->order_sn,
            'type' => $storeOrderStatusRepository::TYPE_ORDER,
            'change_message' => '订单退款已拒绝:'.$refund->refund_order_sn,
            'change_type' => $storeOrderStatusRepository::CHANGE_REFUND_REFUSE,
        ];
        $refundOrderStatus = [
            'order_id' => $refund->refund_order_id,
            'order_sn' => $refund->refund_order_sn,
            'type' => $storeOrderStatusRepository::TYPE_REFUND,
            'change_message' => '订单退款已拒绝',
            'change_type' => $storeOrderStatusRepository::CHANGE_REFUND_REFUSE,
        ];

        Db::transaction(function () use ($id, $data,$refund,$service_id,$storeOrderStatusRepository,$orderStatus,$refundOrderStatus) {

            $data['status_time'] = date('Y-m-d H:i:s');
            $this->getProductRefundNumber($refund, -1);
            $this->dao->update($id, $data);

            if ($service_id) {
                $storeOrderStatusRepository->createServiceLog($service_id,$orderStatus);
                $storeOrderStatusRepository->createServiceLog($service_id,$refundOrderStatus);
            } else {
                $storeOrderStatusRepository->createAdminLog($orderStatus);
                $storeOrderStatusRepository->createAdminLog($refundOrderStatus);
            }

            event('refund.refuse',compact('id','refund'));
            Queue::push(SendSmsJob::class, ['tempId' => 'REFUND_FAIL_CODE', 'id' => $id]);
        });
    }


    /**
     * TODO 同意退款
     * @param $id
     * @param $data
     * @param $adminId
     * @author Qinii
     * @day 2020-06-13
     */
    public function agree(int $id, array $data, $service_id = 0)
    {
        //已退款金额
        $_refund_price = $this->checkRefundPrice($id);

        $refund = $this->dao->getWhere(['refund_order_id' => $id], '*', ['refundProduct.product']);
        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $refund->refund_order_id,
            'order_sn' => $refund->refund_order_sn,
            'type' => $storeOrderStatusRepository::TYPE_REFUND,
        ];
        Db::transaction(function () use ($id, $data, $_refund_price, $refund,$storeOrderStatusRepository,$orderStatus,$service_id) {
            $this->getProductRefundNumber($refund, 1);
            if ($refund['refund_type'] == 1) {
                //TODO 退款单同意退款
                $refund = $this->doRefundPrice($id, $_refund_price);
                $data['status'] = 3;
                $orderStatus['change_message'] = '退款成功';
                $orderStatus['change_type'] = $storeOrderStatusRepository::ORDER_STATUS_CREATE;
                $this->refundAfter($refund);
            }
            if ($refund['refund_type'] == 2) {
                $data['status'] = 1;
                $orderStatus['change_message'] = '退款申请已通过，请将商品寄回';
                $orderStatus['change_type'] = $storeOrderStatusRepository::CHANGE_REFUND_AGREE;
                Queue::push(SendSmsJob::class, ['tempId' => 'REFUND_SUCCESS_CODE', 'id' => $id]);
            }
            $data['status_time'] = date('Y-m-d H:i:s');
            $this->dao->update($id, $data);
            if ($service_id) {
                $storeOrderStatusRepository->createServiceLog($service_id,$orderStatus);
            } else {
                $storeOrderStatusRepository->createAdminLog($orderStatus);
            }
            event('refund.agree', compact('id', 'refund'));
        });
    }

    /**
     * @Author:Qinii
     * @Date: 2020/8/29
     * @param $res
     * @param $status
     * @return bool
     */
    public function getProductRefundNumber($res, $status, $after = false)
    {
        /**
         * 1.同意退款
         *   1.1 仅退款
         *      1.1.1 是 , 如果退款数量 等于 购买数量 is_refund = 3 全退退款 不等于 is_refund = 2 部分退款
         *      1.1.2 否, is_refund = 1 退款中
         *   1.2 退款退货 is_refund = 1
         *
         * 2. 拒绝退款
         *   2.1 如果退款数量 等于 购买数量 返还可退款数 is_refund = 0
         *   2.2 商品总数小于可退数量 返还可退数 以商品数为准
         *   2.3 是否存在其他图款单,是 ,退款中 ,否, 部分退款
         */
        $refundId = $this->getRefundCount($res->order_id, $res['refund_order_id']);
        $make = app()->make(StoreRefundProductRepository::class);
        foreach ($res['refundProduct'] as $item) {
            $is_refund = $item->product->is_refund;
            if ($status == 1) { //同意
                if ($after) {
                    $is_refund = ($item->refund_num == $item->product->product_num) ? 3 : 2;
                } else {
                    if ($res['refund_type'] == 1) {
                        $is_refund = ($item->refund_num == $item->product->product_num) ? 3 : 2;
                    }
                }
            } else {  //拒绝
                $refund_num = $item->refund_num + $item->product->refund_num; //返还可退款数
                if ($item->product->product_num == $refund_num) $is_refund = 0;
                if ($item->product->product_num < $refund_num) $refund_num = $item->product->product_num;
                $item->product->refund_num = $refund_num;
            }
            if (!empty($refundId)) {
                $has = $make->getWhereCount([['refund_order_id', 'in', $refundId], ['order_product_id', '=', $item->product->order_product_id]]);
                if ($has) $is_refund = 1;
            }
            $item->product->is_refund = $is_refund;
            $item->product->save();
        }
        return true;
    }

    /**
     * 获取订单存在的未处理完成的退款单
     * @Author:Qinii
     * @Date: 2020/9/25
     * @param int $orderId
     * @param int|null $refundOrderId
     * @return array
     */
    public function getRefundCount(int $orderId, ?int $refundOrderId)
    {
        $where = [
            'type' => 1,
            'order_id' => $orderId,
        ];

        return $this->dao->search($where)->when($refundOrderId, function ($query) use ($refundOrderId) {
            $query->where('refund_order_id', '<>', $refundOrderId);
        })->column('refund_order_id');
    }

    public function refundGiveIntegral(StoreRefundOrder $refundOrder)
    {
        if ($refundOrder->refund_price > 0 && $refundOrder->order->pay_price > 0) {
            $userBillRepository = app()->make(UserBillRepository::class);
            $bill = $userBillRepository->getWhere(['category' => 'integral', 'type' => 'lock', 'link_id' => $refundOrder->order->group_order_id]);
            if ($bill && $bill->status != 1) {

                if ($refundOrder->order->status == -1) {
                    $number = bcsub($bill->number, $userBillRepository->refundIntegral($refundOrder->order->group_order_id, $bill->uid), 0);
                } else {
                    $number = bcmul(bcdiv($refundOrder['refund_price'], $refundOrder->order->pay_price, 3), $refundOrder->order->give_integral, 0);
                }
                if ($number <= 0) return;

                $userBillRepository->decBill($bill->uid, 'integral', 'refund_lock', [
                    'link_id' => $refundOrder->order->group_order_id,
                    'status' => 1,
                    'title' => '扣除赠送积分',
                    'number' => $number,
                    'mark' => '订单退款扣除赠送积分' . intval($number),
                    'balance' => $refundOrder->user->integral
                ]);
            }
        }
    }

    /**
     * @param StoreRefundOrder $refundOrder
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author xaboy
     * @day 2020/6/17
     */
    public function descBrokerage(StoreRefundOrder $refundOrder)
    {
        $userBillRepository = app()->make(UserBillRepository::class);
        $userRepository = app()->make(UserRepository::class);
        if ($refundOrder['extension_one'] > 0) {
            $bill = $userBillRepository->getWhere(['category' => 'brokerage', 'type' => 'order_one', 'link_id' => $refundOrder->order_id]);
            $refundOrder->order->extension_one = bcsub($refundOrder->order->extension_one, $refundOrder['extension_one'], 2);
            if ($bill && $bill->status != 1) {
                $userRepository->incBrokerage($bill->uid, $refundOrder['extension_one'], '-');
                $userBillRepository->decBill($bill->uid, 'brokerage', 'refund_one', [
                    'link_id' => $refundOrder->order_id,
                    'status' => 1,
                    'title' => '用户退款',
                    'number' => $refundOrder['extension_one'],
                    'mark' => '用户退款扣除推广佣金' . floatval($refundOrder['extension_one']),
                    'balance' => 0
                ]);
            }
            if (!$bill || $bill->status != 1) {
                app()->make(FinancialRecordRepository::class)->inc([
                    'order_id' => $refundOrder->refund_order_id,
                    'order_sn' => $refundOrder->refund_order_sn,
                    'user_info' => $bill ? $userRepository->getUsername($bill->uid) : '退还一级佣金',
                    'user_id' => $bill ? $bill->uid : 0,
                    'type' => 1,
                    'financial_type' => 'refund_brokerage_one',
                    'number' => $refundOrder['extension_one'],
                ], $refundOrder->mer_id);
            }
        }
        if ($refundOrder['extension_two'] > 0) {
            $bill = $userBillRepository->getWhere(['category' => 'brokerage', 'type' => 'order_two', 'link_id' => $refundOrder->order_id]);
            $refundOrder->order->extension_two = bcsub($refundOrder->order->extension_two, $refundOrder['extension_two'], 2);
            if ($bill && $bill->status != 1) {
                $userRepository->incBrokerage($bill->uid, $refundOrder['extension_two'], '-');
                $userBillRepository->decBill($bill->uid, 'brokerage', 'refund_two', [
                    'link_id' => $refundOrder->order_id,
                    'status' => 1,
                    'title' => '用户退款',
                    'number' => $refundOrder['extension_two'],
                    'mark' => '用户退款扣除推广佣金' . floatval($refundOrder['extension_two']),
                    'balance' => 0
                ]);
            }
            if (!$bill || $bill->status != 1) {
                app()->make(FinancialRecordRepository::class)->inc([
                    'order_id' => $refundOrder->refund_order_id,
                    'order_sn' => $refundOrder->refund_order_sn,
                    'user_info' => $bill ? $userRepository->getUsername($bill->uid) : '退还二级佣金',
                    'user_id' => $bill ? $bill->uid : 0,
                    'type' => 1,
                    'financial_type' => 'refund_brokerage_two',
                    'number' => $refundOrder['extension_two'],
                ], $refundOrder->mer_id);
            }
        }
        $refundOrder->order->save();
    }

    /**
     * //TODO 退款后
     * @param StoreRefundOrder $refundOrder
     * @author xaboy
     * @day 2020/6/17
     */
    public function refundAfter(StoreRefundOrder $refundOrder)
    {
        //返还库存
        $refundOrder->append(['refundProduct.product']);
        $productRepository = app()->make(ProductRepository::class);
        if ($refundOrder['refund_type'] == 2 || $refundOrder->order->status == 0 || $refundOrder->order->status == 9) {
            foreach ($refundOrder->refundProduct as $item) {
                $productRepository->orderProductIncStock($refundOrder->order, $item->product, $item->refund_num);
            }
        }
        $refundAll = app()->make(StoreOrderRepository::class)->checkRefundStatusById($refundOrder['order_id'], $refundOrder['refund_order_id']);
        if ($refundAll) {
            $refundOrder->order->status = -1;
        }
        Queue::push(SendSmsJob::class, ['tempId' => 'REFUND_CONFORM_CODE', 'id' => $refundOrder->refund_order_id]);
        $this->descBrokerage($refundOrder);

        //退回平台优惠
        if ($refundOrder->platform_refund_price > 0) {
            if ($refundOrder->order->firstProfitsharing) {
                $model = $refundOrder->order->firstProfitsharing;
                $model->profitsharing_mer_price = bcsub($model->profitsharing_mer_price, $refundOrder->platform_refund_price, 2);
                $model->save();
            } else {
                app()->make(MerchantRepository::class)->subLockMoney($refundOrder->mer_id, 'order', $refundOrder->order->order_id, $refundOrder->platform_refund_price);
            }
            $isVipCoupon = app()->make(StoreGroupOrderRepository::class)->isVipCoupon($refundOrder->order->groupOrder);
            app()->make(FinancialRecordRepository::class)->dec([
                'order_id' => $refundOrder->refund_order_id,
                'order_sn' => $refundOrder->refund_order_sn,
                'user_info' => $refundOrder->user->nickname,
                'user_id' => $refundOrder->uid,
                'financial_type' => $isVipCoupon ? 'refund_svip_coupon' : 'refund_platform_coupon',
                'type' => 1,
                'number' => $refundOrder->platform_refund_price,
            ], $refundOrder->mer_id);
        }

        //退回积分
        if ($refundOrder->integral > 0) {
            $make = app()->make(UserRepository::class);
            $make->update($refundOrder->uid, ['integral' => Db::raw('integral+' . $refundOrder->integral)]);
            $userIntegral = $make->get($refundOrder->uid)->integral;
            $make1 = app()->make(UserBillRepository::class);
            $make1->incBill($refundOrder->uid, 'integral', 'refund', [
                'link_id' => $refundOrder->order_id,
                'status' => 1,
                'title' => '订单退款',
                'number' => $refundOrder->integral,
                'mark' => '订单退款,返还' . intval($refundOrder->integral) . '积分',
                'balance' => $userIntegral
            ]);
            $make1->incBill($refundOrder->uid, 'mer_integral', 'refund', [
                'link_id' => $refundOrder->order_id,
                'status' => 1,
                'title' => '订单退款',
                'number' => $refundOrder->integral,
                'mark' => '订单退款,返还' . intval($refundOrder->integral) . '积分',
                'balance' => $userIntegral,
                'mer_id' => $refundOrder->mer_id
            ]);
        }

        //退还赠送积分
        $this->refundGiveIntegral($refundOrder);

        app()->make(FinancialRecordRepository::class)->dec([
            'order_id' => $refundOrder->refund_order_id,
            'order_sn' => $refundOrder->refund_order_sn,
            'user_info' => $refundOrder->user->nickname,
            'user_id' => $refundOrder->uid,
            'financial_type' => 'refund_order',
            'type' => 1,
            'number' => $refundOrder->refund_price,
        ], $refundOrder->mer_id);
    }

    public function getRefundMerPrice(StoreRefundOrder $refundOrder, $refundPrice = null)
    {
        if ($refundPrice === null) {
            $refundPrice = $refundOrder->refund_price;
            $extension_one = $refundOrder['extension_one'];
            $extension_two = $refundOrder['extension_two'];
        } else {
            $rate = bcdiv($refundPrice, $refundOrder->refund_price, 3);
            $extension_one = $refundOrder['extension_one'] > 0 ? bcmul($rate, $refundOrder['extension_one'], 2) : 0;
            $extension_two = $refundOrder['extension_two'] > 0 ? bcmul($rate, $refundOrder['extension_two'], 2) : 0;
        }
        $extension = bcadd($extension_one, $extension_two, 3);
        $commission_rate = ($refundOrder->order->commission_rate / 100);
        $_refundRate = 0;
        if ($refundOrder->order->commission_rate > 0) {
            $_refundRate = bcmul($commission_rate, bcsub($refundPrice, $extension, 2), 2);
        }
        return bcsub(bcsub($refundPrice, $extension, 2), $_refundRate, 2);
    }


    /**
     * TODO 退款单同意退款退货
     * @param $id
     * @param $admin
     * @author Qinii
     * @day 2020-06-13
     */
    public function adminRefund($id, $service_id = null)
    {
        $refund = $this->dao->getWhere(['refund_order_id' => $id], '*', ['refundProduct.product']);
        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $refund->refund_order_id,
            'order_sn' => $refund->refund_order_sn,
            'type' => $storeOrderStatusRepository::TYPE_REFUND,
            'change_message' => '退款成功',
            'change_type' => $storeOrderStatusRepository::CHANGE_REFUND_PRICE,
        ];
        Db::transaction(function () use ($service_id, $id,$refund,$storeOrderStatusRepository,$orderStatus) {
            $data['status'] = 3;
            $data['status_time'] = date('Y-m-d H:i:s');
            $this->dao->update($id, $data);
            if ($service_id) {
                $storeOrderStatusRepository->createServiceLog($service_id,$orderStatus);
            } else {
                $storeOrderStatusRepository->createAdminLog($orderStatus);
            }
            $this->getProductRefundNumber($refund, 1, true);
            $refund = $this->doRefundPrice($id, 0);
            if ($refund) $this->refundAfter($refund);
        });
    }

    /**
     * TODO 退款操作
     * @param $id
     * @param $adminId
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author Qinii
     * @day 2020-06-13
     */
    public function doRefundPrice($id, $refundPrice)
    {
        $res = $this->dao->getWhere(['refund_order_id' => $id], "*", ['order']);
        if (!$res->order) {
            $res->fail_message = '订单信息不全';
            $res->sataus = -1;
            $res->save();
            return;
        }
        if ($res->refund_price <= 0) return $res;

        if ($res->order->activity_type == 2) {
            $data = $this->getFinalOrder($res, $refundPrice);
        } else {
            if ($res->order->groupOrder->is_combine) {
                $data[] = [
                    'type' => 10,
                    'id' => $res->order->order_id,
                    'sn' => $res->order->groupOrder->group_order_sn,
                    'data' => $res->getCombineRefundParams()
                ];
            } else {
                $data[] = [
                    'type' => $res->order->pay_type,
                    'id' => $res->order->order_id,
                    'sn' => $res->order->groupOrder->group_order_sn,
                    'data' => [
                        'refund_id' => $res->refund_order_sn,
                        'pay_price' => $res->order->groupOrder->pay_price,
                        'refund_price' => $res->refund_price,
                        'refund_message' => $res->refund_message,
                        'open_id' => $res->user->wechat->routine_openid ?? null,
                        'transaction_id' => $res->order->transaction_id,
                    ]
                ];
            }
        }
        $refundPriceAll = 0;
        $refundRate = 0;
        $totalExtension = bcadd($res['extension_one'], $res['extension_two'], 2);
        $_extension = 0;
        $i = count($data);
        foreach ($data as $datum => $item) {
            if ($item['data']['pay_price'] > 0 && $item['data']['refund_price'] > 0) {
                //0余额 1微信 2小程序
                $refundPrice = $this->getRefundMerPrice($res, $item['data']['refund_price']);

                if ($res->order->commission_rate > 0) {
                    $commission_rate = ($res->order->commission_rate / 100);

                    if ($datum == ($i - 1)) {
                        $extension = bcsub($totalExtension, $_extension, 2);
                    } else {
                        $extension = bcmul(bcdiv($item['data']['refund_price'], $res->refund_price, 2), $totalExtension, 2);
                        $_extension = bcadd($_extension, $extension, 2);
                    }
                    $_refundRate = bcmul($commission_rate, bcsub($item['data']['refund_price'], $extension, 2), 2);
                    $refundRate = bcadd($refundRate, $_refundRate, 2);
                }
                $refundPriceAll = bcadd($refundPriceAll, $refundPrice, 2);

                try {
                    $orderType = (isset($item['presell']) && $item['presell']) ? 'presell' : 'order';
                    if ($item['type'] == 0) {
                        $this->refundBill($item, $res->uid, $id);
                        app()->make(MerchantRepository::class)->subLockMoney($res->mer_id, $orderType, $item['id'], $refundPrice);
                    } else {
                        if ($item['type'] == 10) $server = WechatService::create()->combinePay();
                        if (in_array($item['type'], [2])) $server = MiniProgramService::create();
                        if (in_array($item['type'], [4, 5])) $server = AlipayService::create();
                        if (in_array($item['type'], [1, 3, 6])) $server = WechatService::create();
                        $server->payOrderRefund($item['sn'], $item['data']);
                        if ($item['type'] == 10) {
                            $make = app()->make(StoreOrderProfitsharingRepository::class);
                            if ($orderType === 'presell') {
                                $make->refundPresallPrice($res, $item['data']['refund_price'], $refundPrice);
                            } else {
                                $make->refundPrice($res, $item['data']['refund_price'], $refundPrice);
                            }
                        } else {
                            app()->make(MerchantRepository::class)->subLockMoney($res->mer_id, $orderType, $item['id'], $refundPrice);
                        }
                    }
                } catch (Exception $e) {
                    throw new ValidateException($e->getMessage());
                }
            }
        }

        app()->make(FinancialRecordRepository::class)->inc([
            'order_id' => $res->refund_order_id,
            'order_sn' => $res->refund_order_sn,
            'user_info' => $res->user->nickname,
            'user_id' => $res->uid,
            'financial_type' => 'refund_true',
            'number' => $refundPriceAll,
            'type' => 1,
        ], $res->mer_id);

        app()->make(FinancialRecordRepository::class)->inc([
            'order_id' => $res->refund_order_id,
            'order_sn' => $res->refund_order_sn,
            'user_info' => $res->user->nickname,
            'user_id' => $res->uid,
            'type' => 1,
            'financial_type' => 'refund_charge',
            'number' => $refundRate,
        ], $res->mer_id);
        return $res;
    }


    /**
     * TODO 余额退款
     * @param $data
     * @param $uid
     * @param $id
     * @author Qinii
     * @day 2020-11-03
     */
    public function refundBill($data, $uid, $id)
    {
        try {
            $user = app()->make(UserRepository::class)->get($uid);
            $balance = bcadd($user->now_money, $data['data']['refund_price'], 2);
            $user->save(['now_money' => $balance]);

            app()->make(UserBillRepository::class)
                ->incBill($uid, 'now_money', 'refund', [
                'link_id' => $id,
                'status' => 1,
                'title' => '退款增加余额',
                'number' => $data['data']['refund_price'],
                'mark' => '退款增加' . floatval($data['data']['refund_price']) . '余额，退款订单号:' . $data['sn'],
                'balance' => $balance
            ]);
        } catch (Exception $e) {
            throw new ValidateException('余额退款失败');
        }
    }

    public function express($ordertId)
    {
        $refundOrder = $this->dao->get($ordertId);
        return ExpressService::express($refundOrder->delivery_id, $refundOrder->delivery_type, $refundOrder->delivery_phone);
    }

    /**
     *  退款金额是否超过可退金额
     * @Author:Qinii
     * @Date: 2020/9/2
     * @param int $refundId
     * @return bool
     */
    public function checkRefundPrice(int $refundId)
    {
        $refund = $this->dao->get($refundId);
        if($refund['refund_price'] < 0)   throw new ValidateException('退款金额不能小于0');
        $order = app()->make(StoreOrderRepository::class)->get($refund['order_id']);
        $pay_price = $order['pay_price'];

        //预售
        if ($order['activity_type'] == 2) {
            $final_price = app()->make(PresellOrderRepository::class)->getSearch(['order_id' => $refund['order_id']])->value('pay_price');
            $pay_price = bcadd($pay_price, ($final_price ? $final_price : 0), 2);
        }

        //已退金额
        $refund_price = $this->dao->refundPirceByOrder([$refund['order_id']]);

        if (bccomp(bcsub($pay_price, $refund_price, 2), $refund['refund_price'], 2) == -1)
            throw new ValidateException('退款金额超出订单可退金额');

        return $refund_price;
    }

    public function getFinalOrder(StoreRefundOrder $res, $refundPrice)
    {
        /**
         * 1 已退款金额大于定金订单 直接退尾款订单
         * 2 已退款金额小于定金订单
         *   2.1  当前退款金额 大于剩余定金金额 退款两次
         *   2.2  当前退款金额 小于等于剩余定金金额 退款一次
         */
        $result = [];
        if (bccomp($res->order->pay_price, $refundPrice, 2) == -1) {
            $final = app()->make(PresellOrderRepository::class)->getSearch(['order_id' => $res['order_id']])->find();
            if ($final->is_combine) {
                $result[] = [
                    'type' => 10,
                    'id' => $final->presell_order_id,
                    'sn' => $final['presell_order_sn'],
                    'presell' => 1,
                    'data' => [
                        'sub_mchid' => $res->merchant->sub_mchid,
                        'order_sn' => $res->order->order_sn,
                        'refund_order_sn' => $res->refund_order_sn,
                        'pay_price' => $res->order->pay_price,
                        'refund_price' => $res->refund_price,
                    ]
                ];
            } else {
                $result[] = [
                    'type' => $final->is_combine ? 10 : $final->pay_type,
                    'id' => $final->presell_order_id,
                    'sn' => $final['presell_order_sn'],
                    'data' => [
                        'refund_id' => $res->refund_order_sn,
                        'pay_price' => $res->order->pay_price,
                        'refund_price' => $res->refund_price
                    ]
                ];
            }
        } else {
            //定金金额 - 已退款金额 = 剩余定金
            $sub_order_price = bcsub($res->order->pay_price, $refundPrice, 2);
            //剩余定金于此次退款金额对比
            $sub_comp = bccomp($sub_order_price, $res->refund_price, 2);
            //定金订单
            if ($sub_comp == 1 || $sub_comp == 0) {
                if ($res->order->groupOrder->is_combine) {
                    $result[] = [
                        'type' => 10,
                        'id' => $res->order->order_id,
                        'sn' => $res->order->order_sn,
                        'data' => $res->getCombineRefundParams()
                    ];
                } else {
                    $result[] = [
                        'type' => $res->order->pay_type,
                        'id' => $res->order->order_id,
                        'sn' => $res->order->groupOrder->group_order_sn,
                        'data' => [
                            'refund_id' => $res->refund_order_sn,
                            'pay_price' => $res->order->pay_price,
                            'refund_price' => $res->refund_price
                        ]
                    ];
                }
            }

            //两个分别计算
            if ($sub_comp == -1) {
                if ($res->order->groupOrder->is_combine) {
                    $data = $res->getCombineRefundParams();
                    $data['refund_price'] = $sub_order_price;
                    $result[] = [
                        'type' => 10,
                        'id' => $res->order->order_id,
                        'sn' => $res->order->order_sn,
                        'data' => $data
                    ];
                } else {
                    $result[] = [
                        'type' => $res->order->pay_type,
                        'sn' => $res->order->groupOrder->group_order_sn,
                        'id' => $res->order->order_id,
                        'data' => [
                            'refund_id' => $res->refund_order_sn,
                            'pay_price' => $res->order->pay_price,
                            'refund_price' => $sub_order_price
                        ]
                    ];
                }

                $final = app()->make(PresellOrderRepository::class)->getSearch(['order_id' => $res['order_id']])->find();
                if ($final->is_combine) {
                    $result[] = [
                        'type' => 10,
                        'id' => $final->presell_order_id,
                        'sn' => $final['presell_order_sn'],
                        'presell' => 1,
                        'data' => [
                            'sub_mchid' => $res->merchant->sub_mchid,
                            'order_sn' => $final['presell_order_sn'],
                            'refund_order_sn' => $res->refund_order_sn . '1',
                            'pay_price' => $final->pay_price,
                            'refund_price' => bcsub($res->refund_price, $sub_order_price, 2)
                        ]
                    ];
                } else {
                    $result[] = [
                        'type' => $final->is_combine ? 10 : $final->pay_type,
                        'id' => $final->presell_order_id,
                        'sn' => $final['presell_order_sn'] . '1',
                        'data' => [
                            'refund_id' => $final['presell_order_sn'],
                            'pay_price' => $final->pay_price,
                            'refund_price' => bcsub($res->refund_price, $sub_order_price, 2)
                        ]
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * TODO 订单自动退款
     * @param $id
     * @param int $refund_type
     * @param string $message
     * @author Qinii
     * @day 1/15/21
     */
    public function autoRefundOrder($id, $refund_type = 1, $message = '')
    {
        $order = app()->make(StoreOrderRepository::class)->get($id);
        if (!$order) return;
        if ($order->status == -1) return;
        if ($order['paid'] == 1) {
            //已支付
            $refund_make = app()->make(StoreRefundOrderRepository::class);
            $refund = $refund_make->createRefund($order, $refund_type, $message);
            $refund_make->agree($refund[$refund_make->getPk()], [], 0);
        } else {
            if (!$order->is_del) {
                app()->make(StoreOrderRepository::class)->delOrder($order, $message);
            }
        }
    }


    /**
     * TODO 移动端客服退款信息
     * @param int $id
     * @param int $merId
     * @return array
     * @author Qinii
     * @day 6/2/22
     */
    public function serverRefundDetail(int $id, int $merId)
    {
        if (!$this->dao->merHas($merId, $id)) {
            throw new ValidateException('数据不存在');
        }
        $data = $this->dao->getWhere(['mer_id' => $merId, $this->dao->getPk() => $id],'*', ['refundProduct.product','order']);
        $total_price = $total_postage = 0.00;

        foreach ($data['refundProduct'] as $itme) {
            $total_price = bcadd($total_price , $itme['refund_price'],2);
            $total_postage = bcadd($total_postage , $itme['postage_price'], 2);
        }
        $data['total_num'] = $data['order']['total_num'];
        unset($data['refundProduct'],$data['order']);
        $data['total_price'] = $total_price;
        $data['total_postage'] = $total_postage;
        $refund_info = null;
        if ($data['refund_type'] == 2) {
            $refund_info['mer_delivery_user'] = merchantConfig($merId,'mer_refund_user');
            $refund_info['mer_delivery_address'] = merchantConfig($merId,'mer_refund_address');
            $refund_info['phone'] = merchantConfig($merId,'set_phone');
        }
        $data['refund_info'] = $refund_info;

        return $data;
    }

    /**
     * TODO 用户取消退款单申请
     * @param int $id
     * @param $user
     * @author Qinii
     * @day 2022/11/18
     */
    public function cancel(int $id, $user)
    {
        //状态 0:待审核 -1:审核未通过 1:待退货 2:待收货 3:已退款
        $refund = $this->dao->getWhere(['refund_order_id' => $id, 'uid' => $user->uid],'*', ['refundProduct.product']);
        if (!$refund) throw new ValidateException('数据不存在');
        if (!in_array($refund['status'],[self::REFUND_STATUS_WAIT, self::REFUND_STATUS_BACK]))
            throw new ValidateException('当前状态不可取消');

        //退款订单记录
        $storeOrderStatusRepository = app()->make(StoreOrderStatusRepository::class);
        $orderStatus = [
            'order_id' => $refund->refund_order_id,
            'order_sn' => $refund->refund_order_sn,
            'type' => $storeOrderStatusRepository::TYPE_REFUND,
            'change_message' => '用户取消退款',
            'change_type' => $storeOrderStatusRepository::CHANGE_REFUND_CANCEL,
        ];

        Db::transaction(function () use ($id, $refund,$storeOrderStatusRepository,$orderStatus) {
            $this->getProductRefundNumber($refund, -1);
            $this->dao->update($id, ['status_time' => date('Y-m-d H:i:s'), 'status' => self::REFUND_STATUS_CANCEL]);
            $storeOrderStatusRepository->createUserLog($orderStatus);
        });
    }
}
